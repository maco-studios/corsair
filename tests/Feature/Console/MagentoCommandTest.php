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
use Corsair\Console\MagentoCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\Process;

beforeEach(function (): void {
    putenv('CORSAIR_SKIP_DOCKER=1');

    $this->project = temporaryProjectPath('corsair-magento');
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

it('forwards bin/magento arguments into the magento.test container', function (): void {
    $installer = (new InstallCommand())->setProjectPath($this->project);
    (new CommandTester($installer))->execute(['--platform' => 'magento'], ['interactive' => false]);

    mkdir($this->project . '/bin', 0755, true);
    file_put_contents($this->project . '/bin/magento', "#!/usr/bin/env php\n");

    $tester = new CommandTester((new MagentoCommand())->setProjectPath($this->project));

    $status = $tester->execute(['args' => ['cache:flush']], ['interactive' => false]);

    expect($status)->toBe(0)
        ->and($tester->getDisplay())->toContain("'magento.test'")
        ->and($tester->getDisplay())->toContain("'bin/magento'")
        ->and($tester->getDisplay())->toContain("'cache:flush'");
});

it('forwards bin/magento arguments into the mageos.test container', function (): void {
    $installer = (new InstallCommand())->setProjectPath($this->project);
    (new CommandTester($installer))->execute(['--platform' => 'mageos'], ['interactive' => false]);

    mkdir($this->project . '/bin', 0755, true);
    file_put_contents($this->project . '/bin/magento', "#!/usr/bin/env php\n");

    $tester = new CommandTester((new MagentoCommand())->setProjectPath($this->project));

    $status = $tester->execute(['args' => ['indexer:reindex']], ['interactive' => false]);

    expect($status)->toBe(0)
        ->and($tester->getDisplay())->toContain("'mageos.test'")
        ->and($tester->getDisplay())->toContain("'indexer:reindex'");
});

it('forwards arguments from the corsair binary', function (): void {
    $installer = (new InstallCommand())->setProjectPath($this->project);
    (new CommandTester($installer))->execute(['--platform' => 'magento'], ['interactive' => false]);

    mkdir($this->project . '/bin', 0755, true);
    file_put_contents($this->project . '/bin/magento', "#!/usr/bin/env php\n");

    $process = new Process(
        [PHP_BINARY, dirname(__DIR__, 3) . '/bin/corsair', 'magento', 'setup:upgrade'],
        $this->project,
        ['CORSAIR_SKIP_DOCKER' => '1'],
    );
    $process->run();

    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput() . $process->getErrorOutput())->toContain("'setup:upgrade'");
});

it('refuses to run without bin/magento', function (): void {
    $installer = (new InstallCommand())->setProjectPath($this->project);
    (new CommandTester($installer))->execute(['--platform' => 'magento'], ['interactive' => false]);

    $tester = new CommandTester((new MagentoCommand())->setProjectPath($this->project));

    $status = $tester->execute([], ['interactive' => false]);

    expect($status)->toBe(1);
});

it('refuses to run on platforms without bin/magento even if the file exists', function (): void {
    $installer = (new InstallCommand())->setProjectPath($this->project);
    (new CommandTester($installer))->execute(['--platform' => 'maho'], ['interactive' => false]);

    mkdir($this->project . '/bin', 0755, true);
    file_put_contents($this->project . '/bin/magento', "#!/usr/bin/env php\n");

    $tester = new CommandTester((new MagentoCommand())->setProjectPath($this->project));

    $status = $tester->execute([], ['interactive' => false]);

    expect($status)->toBe(1);
});
