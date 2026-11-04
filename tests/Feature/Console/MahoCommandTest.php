<?php

/**
 * Copyright (c) 2026 Marcos "Marcão" Aurelio
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/maco-studios/corsair
 */

use Corsair\Console\AddCommand;
use Corsair\Console\InstallCommand;
use Corsair\Console\MahoCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

beforeEach(function (): void {
    putenv('CORSAIR_SKIP_DOCKER=1');

    $this->project = temporaryProjectPath('corsair-maho');
    mkdir($this->project, 0755, true);

    $installer = (new InstallCommand())->setProjectPath($this->project);
    (new CommandTester($installer))->execute([
        '--platform' => 'maho',
    ], ['interactive' => false]);

    file_put_contents($this->project . '/maho', "#!/usr/bin/env php\n");
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

it('forwards maho cli arguments into the application container', function (): void {
    $tester = new CommandTester((new MahoCommand())->setProjectPath($this->project));

    $status = $tester->execute([
        'args' => ['cache:flush'],
    ], ['interactive' => false]);

    expect($status)->toBe(0)
        ->and($tester->getDisplay())->toContain("'maho.test'")
        ->and($tester->getDisplay())->toContain("'./maho'")
        ->and($tester->getDisplay())->toContain("'cache:flush'");
});

it('forwards arguments from the corsair binary', function (): void {
    $process = new Process(
        [PHP_BINARY, dirname(__DIR__, 3) . '/bin/corsair', 'maho', 'index:reindex:all'],
        $this->project,
        ['CORSAIR_SKIP_DOCKER' => '1'],
    );
    $process->run();

    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput() . $process->getErrorOutput())->toContain("'index:reindex:all'");
});

it('adds a service to the maho app container', function (): void {
    $tester = new CommandTester((new AddCommand())->setProjectPath($this->project));

    $status = $tester->execute([
        'services' => 'mailpit',
    ], ['interactive' => false]);

    $compose = Yaml::parseFile($this->project . '/compose.yaml');

    expect($status)->toBe(0)
        ->and($compose['services'])->toHaveKey('maho.test')
        ->and($compose['services'])->not->toHaveKey('magento.test')
        ->and($compose['services']['maho.test']['depends_on'])->toContain('mysql', 'redis', 'mailpit')
        ->and($compose['services']['redis']['command'])->toContain('volatile-lfu');
});

it('refuses to run when the maho.test service is missing', function (): void {
    $otherProject = temporaryProjectPath('corsair-maho-wrong-platform');
    mkdir($otherProject, 0755, true);

    $installer = (new InstallCommand())->setProjectPath($otherProject);
    (new CommandTester($installer))->execute(['--platform' => 'magento'], ['interactive' => false]);
    file_put_contents($otherProject . '/maho', "#!/usr/bin/env php\n");

    $tester = new CommandTester((new MahoCommand())->setProjectPath($otherProject));
    $status = $tester->execute([], ['interactive' => false]);

    expect($status)->toBe(1);

    removeDirectoryRecursively($otherProject);
});

it('runs the real maho command when not skipping docker', function (): void {
    putenv('CORSAIR_SKIP_DOCKER=0');
    $status = withFakeDocker(fn(): int => (new MahoCommand())->setProjectPath($this->project)->forward(['cache:flush']));

    expect($status)->toBe(0);
});

it('fails the real maho command when the docker command exits non-zero', function (): void {
    putenv('CORSAIR_SKIP_DOCKER=0');
    $status = withFakeDocker(fn(): int => (new MahoCommand())->setProjectPath($this->project)->forward(['cache:flush']), exitCode: 1);

    expect($status)->toBe(1);
});

it('refuses to run without the maho cli', function (): void {
    unlink($this->project . '/maho');

    $tester = new CommandTester((new MahoCommand())->setProjectPath($this->project));

    $status = $tester->execute([], ['interactive' => false]);

    expect($status)->toBe(1);
});
