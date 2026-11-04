<?php

/**
 * Copyright (c) 2026 Marcos "Marcão" Aurelio
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/maco-studios/corsair
 */

use Corsair\Console\DockerComposeCommand;
use Corsair\Console\InstallCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\Process;

beforeEach(function (): void {
    putenv('CORSAIR_SKIP_DOCKER=1');

    $this->project = temporaryProjectPath('corsair-compose');
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

it('forwards arbitrary arguments to docker compose', function (): void {
    $installer = (new InstallCommand())->setProjectPath($this->project);
    (new CommandTester($installer))->execute(['--platform' => 'magento'], ['interactive' => false]);

    $command = (new DockerComposeCommand())->setProjectPath($this->project);

    $status = $command->forward(['up', '-d']);

    expect($status)->toBe(0);
});

it('refuses to forward when no compose file exists yet', function (): void {
    $command = (new DockerComposeCommand())->setProjectPath($this->project);

    $status = $command->forward(['up', '-d']);

    expect($status)->toBe(1);
});

it('aliases vendor/bin/corsair up to docker compose up', function (): void {
    $installer = (new InstallCommand())->setProjectPath($this->project);
    (new CommandTester($installer))->execute(['--platform' => 'magento'], ['interactive' => false]);

    $process = new Process(
        [PHP_BINARY, dirname(__DIR__, 3) . '/bin/corsair', 'up', '-d'],
        $this->project,
        ['CORSAIR_SKIP_DOCKER' => '1'],
    );
    $process->run();

    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput() . $process->getErrorOutput())->toContain('docker compose')
        ->and($process->getOutput() . $process->getErrorOutput())->toContain("'up'")
        ->and($process->getOutput() . $process->getErrorOutput())->toContain("'-d'");
});
