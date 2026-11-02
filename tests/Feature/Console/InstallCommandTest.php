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
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;

beforeEach(function (): void {
    putenv('CORSAIR_SKIP_DOCKER=1');

    $this->project = temporaryProjectPath('corsair-install');
    mkdir($this->project, 0755, true);
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

it('installs compose services and env defaults non-interactively', function (): void {
    $command = (new InstallCommand())->setProjectPath($this->project);
    $tester = new CommandTester($command);

    $status = $tester->execute([
        '--with' => 'mysql,redis,opensearch',
        '--php' => '8.3',
    ], ['interactive' => false]);

    expect($status)->toBe(0)
        ->and(file_exists($this->project . '/compose.yaml'))->toBeTrue()
        ->and(file_exists($this->project . '/.env'))->toBeTrue();

    $compose = Yaml::parseFile($this->project . '/compose.yaml');

    expect($compose['services'])->toHaveKeys(['magento.test', 'mysql', 'redis', 'opensearch'])
        ->and($compose['services']['magento.test']['depends_on'])->toContain('mysql', 'redis', 'opensearch')
        ->and($compose['volumes'])->toHaveKeys(['corsair-mysql', 'corsair-redis', 'corsair-opensearch'])
        ->and($compose['services']['mysql']['volumes'])->toContain(
            './vendor/maco-studios/corsair/resources/database/mysql/create-testing-database.sh:/docker-entrypoint-initdb.d/10-create-testing-database.sh',
        );

    $env = file_get_contents($this->project . '/.env');

    expect($env)
        ->toContain('DB_HOST=mysql')
        ->toContain('REDIS_HOST=redis')
        ->toContain('OPENSEARCH_HOST=opensearch');
});

it('rejects invalid services', function (): void {
    $command = (new InstallCommand())->setProjectPath($this->project);
    $tester = new CommandTester($command);

    $status = $tester->execute([
        '--with' => 'mysql,not-a-service',
    ], ['interactive' => false]);

    expect($status)->toBe(1)
        ->and(file_exists($this->project . '/compose.yaml'))->toBeFalse();
});
