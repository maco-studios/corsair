<?php

/**
 * Copyright (c) 2026 Marcos "Marcão" Aurelio
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/maco-studios/corsair
 */

use Corsair\Console\InstallCommand;
use Corsair\Console\PublishCommand;
use Symfony\Component\Console\Tester\CommandTester;

beforeEach(function (): void {
    putenv('CORSAIR_SKIP_DOCKER=1');

    $this->project = temporaryProjectPath('corsair-publish');
    mkdir($this->project, 0755, true);

    $installer = (new InstallCommand())->setProjectPath($this->project);
    (new CommandTester($installer))->execute([
        '--with' => 'mysql',
        '--php' => '8.3',
    ], ['interactive' => false]);
});

afterEach(function (): void {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->project, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iterator as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }

    rmdir($this->project);
    putenv('CORSAIR_SKIP_DOCKER');
});

it('publishes runtimes and rewrites compose build context', function (): void {
    $command = (new PublishCommand())->setProjectPath($this->project);
    $tester = new CommandTester($command);

    $status = $tester->execute([]);

    expect($status)->toBe(0)
        ->and(file_exists($this->project.'/docker/8.3/Dockerfile'))->toBeTrue()
        ->and(file_exists($this->project.'/docker/8.3/conf/php.ini'))->toBeTrue()
        ->and(file_exists($this->project.'/docker/8.3/conf/nginx.conf'))->toBeTrue()
        ->and(file_get_contents($this->project.'/docker/8.3/conf/php.ini'))->toContain('memory_limit = 4G')
        ->and(file_exists($this->project.'/docker/mysql/create-testing-database.sh'))->toBeTrue()
        ->and(file_exists($this->project.'/docker/mariadb/create-testing-database.sh'))->toBeTrue();

    $compose = file_get_contents($this->project.'/compose.yaml');

    expect($compose)
        ->toContain('./docker/8.3')
        ->toContain('./docker/mysql/create-testing-database.sh')
        ->not->toContain('./vendor/maco-studios/corsair/resources/runtimes/8.3')
        ->not->toContain('./vendor/maco-studios/corsair/resources/database/mysql');
});
