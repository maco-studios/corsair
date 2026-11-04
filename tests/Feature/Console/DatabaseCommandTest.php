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
use Corsair\Console\DatabaseCommand;
use Corsair\Console\InstallCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\Process;

beforeEach(function (): void {
    putenv('CORSAIR_SKIP_DOCKER=1');

    $this->project = temporaryProjectPath('corsair-db');
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

it('refuses db:query, db:dump, and db:import without a compose file', function (): void {
    $command = new DatabaseCommand();
    $command->setProjectPath($this->project);

    expect($command->query())->toBe(1)
        ->and($command->dump([]))->toBe(1)
        ->and($command->import(['dump.sql']))->toBe(1);
});

it('refuses db:query when no mysql or mariadb service is installed', function (): void {
    $installer = (new InstallCommand())->setProjectPath($this->project);
    (new CommandTester($installer))->execute(['--platform' => 'magento', '--with' => 'none'], ['interactive' => false]);

    $status = (new DatabaseCommand())->setProjectPath($this->project)->query();

    expect($status)->toBe(1);
});

it('refuses db:import when the source file does not exist', function (): void {
    $installer = (new InstallCommand())->setProjectPath($this->project);
    (new CommandTester($installer))->execute(['--platform' => 'magento', '--with' => 'mysql'], ['interactive' => false]);

    $status = (new DatabaseCommand())->setProjectPath($this->project)->import(['missing.sql']);

    expect($status)->toBe(1);
});

it('builds the mysqldump command for db:dump from the .env credentials', function (): void {
    $installer = (new InstallCommand())->setProjectPath($this->project);
    (new CommandTester($installer))->execute(['--platform' => 'magento', '--with' => 'mysql'], ['interactive' => false]);

    $process = new Process(
        [PHP_BINARY, dirname(__DIR__, 3) . '/bin/corsair', 'db:dump', 'dump.sql'],
        $this->project,
        ['CORSAIR_SKIP_DOCKER' => '1'],
    );
    $process->run();

    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toContain("docker compose exec -T 'mysql' 'mysqldump' '-ucorsair' '-ppassword' 'magento' > dump.sql");
});

it('uses the mariadb client when mariadb is installed instead of mysql', function (): void {
    $installer = (new InstallCommand())->setProjectPath($this->project);
    (new CommandTester($installer))->execute(['--platform' => 'magento', '--with' => 'mariadb'], ['interactive' => false]);

    $process = new Process(
        [PHP_BINARY, dirname(__DIR__, 3) . '/bin/corsair', 'db:dump', 'dump.sql'],
        $this->project,
        ['CORSAIR_SKIP_DOCKER' => '1'],
    );
    $process->run();

    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toContain("docker compose exec -T 'mariadb' 'mariadb-dump'");
});

it('adds mysql after a bare install and lets db:query find it', function (): void {
    $installer = (new InstallCommand())->setProjectPath($this->project);
    (new CommandTester($installer))->execute(['--platform' => 'magento', '--with' => 'none'], ['interactive' => false]);

    $adder = (new AddCommand())->setProjectPath($this->project);
    (new CommandTester($adder))->execute(['services' => 'mysql'], ['interactive' => false]);

    $status = (new DatabaseCommand())->setProjectPath($this->project)->query();

    expect($status)->toBe(0);
});

it('prints db:dump and db:import commands in-process when skipping docker', function (): void {
    $installer = (new InstallCommand())->setProjectPath($this->project);
    (new CommandTester($installer))->execute(['--platform' => 'magento', '--with' => 'mysql'], ['interactive' => false]);

    $source = $this->project . '/dump.sql';
    file_put_contents($source, '-- dump');

    $dumpStatus = (new DatabaseCommand())->setProjectPath($this->project)->dump([$this->project . '/dump-output.sql']);
    $importStatus = (new DatabaseCommand())->setProjectPath($this->project)->import(['dump.sql']);

    expect($dumpStatus)->toBe(0)
        ->and($importStatus)->toBe(0);
});

it('refuses db:import when no source file argument is given at all', function (): void {
    $installer = (new InstallCommand())->setProjectPath($this->project);
    (new CommandTester($installer))->execute(['--platform' => 'magento', '--with' => 'mysql'], ['interactive' => false]);

    $status = (new DatabaseCommand())->setProjectPath($this->project)->import([]);

    expect($status)->toBe(1);
});

it('has an inert execute() for when Symfony runs it directly', function (): void {
    $status = (new CommandTester(new DatabaseCommand()))->execute([]);

    expect($status)->toBe(0);
});

it('runs the real db:query command when not skipping docker', function (): void {
    $installer = (new InstallCommand())->setProjectPath($this->project);
    (new CommandTester($installer))->execute(['--platform' => 'magento', '--with' => 'mysql'], ['interactive' => false]);

    putenv('CORSAIR_SKIP_DOCKER=0');
    $status = withFakeDocker(fn(): int => (new DatabaseCommand())->setProjectPath($this->project)->query());

    expect($status)->toBe(0);
});

it('fails db:query when the real docker command exits non-zero', function (): void {
    $installer = (new InstallCommand())->setProjectPath($this->project);
    (new CommandTester($installer))->execute(['--platform' => 'magento', '--with' => 'mysql'], ['interactive' => false]);

    putenv('CORSAIR_SKIP_DOCKER=0');
    $status = withFakeDocker(fn(): int => (new DatabaseCommand())->setProjectPath($this->project)->query(), exitCode: 1);

    expect($status)->toBe(1);
});

it('dumps the database to a file when not skipping docker', function (): void {
    $installer = (new InstallCommand())->setProjectPath($this->project);
    (new CommandTester($installer))->execute(['--platform' => 'magento', '--with' => 'mysql'], ['interactive' => false]);

    $destination = $this->project . '/dump.sql';

    putenv('CORSAIR_SKIP_DOCKER=0');
    $status = withFakeDocker(fn(): int => (new DatabaseCommand())->setProjectPath($this->project)->dump([$destination]));

    expect($status)->toBe(0)
        ->and(is_file($destination))->toBeTrue()
        ->and(file_get_contents($destination))->toContain('fake docker:');
});

it('reports a failure when the real db:dump command exits non-zero', function (): void {
    $installer = (new InstallCommand())->setProjectPath($this->project);
    (new CommandTester($installer))->execute(['--platform' => 'magento', '--with' => 'mysql'], ['interactive' => false]);

    putenv('CORSAIR_SKIP_DOCKER=0');
    $status = withFakeDocker(fn(): int => (new DatabaseCommand())->setProjectPath($this->project)->dump([$this->project . '/dump.sql']), exitCode: 1);

    expect($status)->toBe(1);
});

it('refuses db:dump when the destination cannot be opened for writing', function (): void {
    $installer = (new InstallCommand())->setProjectPath($this->project);
    (new CommandTester($installer))->execute(['--platform' => 'magento', '--with' => 'mysql'], ['interactive' => false]);

    putenv('CORSAIR_SKIP_DOCKER=0');
    $status = withWarningsSuppressed(fn(): int => withFakeDocker(
        fn(): int => (new DatabaseCommand())->setProjectPath($this->project)->dump([$this->project . '/no-such-directory/dump.sql']),
    ));

    expect($status)->toBe(1);
});

it('imports an SQL file when not skipping docker', function (): void {
    $installer = (new InstallCommand())->setProjectPath($this->project);
    (new CommandTester($installer))->execute(['--platform' => 'magento', '--with' => 'mysql'], ['interactive' => false]);

    $source = $this->project . '/dump.sql';
    file_put_contents($source, '-- dump');

    putenv('CORSAIR_SKIP_DOCKER=0');
    $status = withFakeDocker(fn(): int => (new DatabaseCommand())->setProjectPath($this->project)->import(['dump.sql']));

    expect($status)->toBe(0);
});

it('reports a failure when the real db:import command exits non-zero', function (): void {
    $installer = (new InstallCommand())->setProjectPath($this->project);
    (new CommandTester($installer))->execute(['--platform' => 'magento', '--with' => 'mysql'], ['interactive' => false]);

    $source = $this->project . '/dump.sql';
    file_put_contents($source, '-- dump');

    putenv('CORSAIR_SKIP_DOCKER=0');
    $status = withFakeDocker(fn(): int => (new DatabaseCommand())->setProjectPath($this->project)->import(['dump.sql']), exitCode: 1);

    expect($status)->toBe(1);
});
