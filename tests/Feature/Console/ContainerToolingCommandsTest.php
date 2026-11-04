<?php

/**
 * Copyright (c) 2026 Marcos "Marcão" Aurelio
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/maco-studios/corsair
 */

use Corsair\Console\ComposerCommand;
use Corsair\Console\InstallCommand;
use Corsair\Console\NpmCommand;
use Corsair\Console\ShellCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\Process;

beforeEach(function (): void {
    putenv('CORSAIR_SKIP_DOCKER=1');

    $this->project = temporaryProjectPath('corsair-tooling');
    mkdir($this->project, 0755, true);

    $installer = (new InstallCommand())->setProjectPath($this->project);
    (new CommandTester($installer))->execute(['--platform' => 'magento'], ['interactive' => false]);
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

it('opens a shell in the application container', function (): void {
    $status = (new ShellCommand())->setProjectPath($this->project)->forward([]);

    expect($status)->toBe(0);
});

it('forwards npm arguments into the application container', function (): void {
    $process = new Process(
        [PHP_BINARY, dirname(__DIR__, 3) . '/bin/corsair', 'npm', 'install'],
        $this->project,
        ['CORSAIR_SKIP_DOCKER' => '1'],
    );
    $process->run();

    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toContain("'magento.test'")
        ->and($process->getOutput())->toContain("'npm'")
        ->and($process->getOutput())->toContain("'install'");
});

it('forwards composer arguments into the application container', function (): void {
    $process = new Process(
        [PHP_BINARY, dirname(__DIR__, 3) . '/bin/corsair', 'composer', 'install', '--no-dev'],
        $this->project,
        ['CORSAIR_SKIP_DOCKER' => '1'],
    );
    $process->run();

    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toContain("'magento.test'")
        ->and($process->getOutput())->toContain("'composer'")
        ->and($process->getOutput())->toContain("'--no-dev'");
});

it('runs the real shell/npm/composer commands when not skipping docker', function (): void {
    putenv('CORSAIR_SKIP_DOCKER=0');

    $shellStatus = withFakeDocker(fn(): int => (new ShellCommand())->setProjectPath($this->project)->forward([]));
    $npmStatus = withFakeDocker(fn(): int => (new NpmCommand())->setProjectPath($this->project)->forward(['install']));
    $composerStatus = withFakeDocker(fn(): int => (new ComposerCommand())->setProjectPath($this->project)->forward(['install']));

    expect($shellStatus)->toBe(0)
        ->and($npmStatus)->toBe(0)
        ->and($composerStatus)->toBe(0);
});

it('fails shell/npm/composer when the real docker command exits non-zero', function (): void {
    putenv('CORSAIR_SKIP_DOCKER=0');

    $shellStatus = withFakeDocker(fn(): int => (new ShellCommand())->setProjectPath($this->project)->forward([]), exitCode: 1);
    $npmStatus = withFakeDocker(fn(): int => (new NpmCommand())->setProjectPath($this->project)->forward(['install']), exitCode: 1);
    $composerStatus = withFakeDocker(fn(): int => (new ComposerCommand())->setProjectPath($this->project)->forward(['install']), exitCode: 1);

    expect($shellStatus)->toBe(1)
        ->and($npmStatus)->toBe(1)
        ->and($composerStatus)->toBe(1);
});

it('has an inert execute() for shell/npm/composer when Symfony runs them directly', function (): void {
    expect((new CommandTester(new ShellCommand()))->execute([]))->toBe(0)
        ->and((new CommandTester(new NpmCommand()))->execute([]))->toBe(0)
        ->and((new CommandTester(new ComposerCommand()))->execute([]))->toBe(0);
});

it('refuses to open a shell or run npm/composer before install', function (): void {
    $empty = temporaryProjectPath('corsair-tooling-empty');
    mkdir($empty, 0755, true);

    expect((new ShellCommand())->setProjectPath($empty)->forward([]))->toBe(1)
        ->and((new NpmCommand())->setProjectPath($empty)->forward(['install']))->toBe(1)
        ->and((new ComposerCommand())->setProjectPath($empty)->forward(['install']))->toBe(1);

    rmdir($empty);
});
