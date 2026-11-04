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
use Corsair\Console\MahoInstallCommand;
use Symfony\Component\Console\Tester\CommandTester;

beforeEach(function (): void {
    putenv('CORSAIR_SKIP_DOCKER=1');

    $this->project = temporaryProjectPath('corsair-maho-install');
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

it('builds a maho install command from the environment', function (): void {
    $tester = new CommandTester((new MahoInstallCommand())->setProjectPath($this->project));

    $status = $tester->execute([
        '--sample-data' => true,
    ], ['interactive' => false]);

    $display = $tester->getDisplay();

    expect($status)->toBe(0)
        ->and($display)->toContain("'maho.test'")
        ->and($display)->toContain("'--db_host=mysql'")
        ->and($display)->toContain("'--db_name=maho'")
        ->and($display)->toContain("'--db_user=corsair'")
        ->and($display)->toContain("'--db_pass=password'")
        ->and($display)->toContain("'--url=http://localhost/'")
        ->and($display)->toContain("'--license_agreement_accepted=yes'")
        ->and($display)->toContain("'--sample_data=1'")
        ->and($display)->toContain('index:reindex:all')
        ->and($display)->toContain('cache:flush')
        ->and($display)->toContain('redis://redis/0')
        ->and($display)->toContain('redis://redis/1');
});

it('skips reindexing when asked', function (): void {
    $tester = new CommandTester((new MahoInstallCommand())->setProjectPath($this->project));

    $status = $tester->execute([
        '--skip-reindex' => true,
        '--url' => 'http://maho.test',
    ], ['interactive' => false]);

    $display = $tester->getDisplay();

    expect($status)->toBe(0)
        ->and($display)->toContain("'--url=http://maho.test/'")
        ->and($display)->not->toContain('index:reindex:all');
});

it('rejects a short admin password', function (): void {
    $tester = new CommandTester((new MahoInstallCommand())->setProjectPath($this->project));

    $status = $tester->execute([
        '--admin-password' => 'short',
    ], ['interactive' => false]);

    expect($status)->toBe(1);
});

it('refuses to install without a compose file', function (): void {
    $empty = temporaryProjectPath('corsair-maho-install-empty');
    mkdir($empty, 0755, true);

    $tester = new CommandTester((new MahoInstallCommand())->setProjectPath($empty));
    $status = $tester->execute([], ['interactive' => false]);

    expect($status)->toBe(1);

    removeDirectoryRecursively($empty);
});

it('refuses to install on a non-maho compose stack', function (): void {
    $other = temporaryProjectPath('corsair-maho-install-wrong-platform');
    mkdir($other, 0755, true);

    $installer = (new InstallCommand())->setProjectPath($other);
    (new CommandTester($installer))->execute(['--platform' => 'magento'], ['interactive' => false]);

    $tester = new CommandTester((new MahoInstallCommand())->setProjectPath($other));
    $status = $tester->execute([], ['interactive' => false]);

    expect($status)->toBe(1);

    removeDirectoryRecursively($other);
});

it('refuses to install without the maho cli', function (): void {
    unlink($this->project . '/maho');

    $tester = new CommandTester((new MahoInstallCommand())->setProjectPath($this->project));
    $status = $tester->execute([], ['interactive' => false]);

    expect($status)->toBe(1);
});

it('installs for real and reports success when not skipping docker', function (): void {
    putenv('CORSAIR_SKIP_DOCKER=0');
    $tester = new CommandTester((new MahoInstallCommand())->setProjectPath($this->project));

    $status = withFakeDocker(fn(): int => $tester->execute([], ['interactive' => false]));

    expect($status)->toBe(0)
        ->and($tester->getDisplay())->toContain('MahoCommerce installed.');
});

it('reports a failure when the real install command exits non-zero', function (): void {
    putenv('CORSAIR_SKIP_DOCKER=0');
    $tester = new CommandTester((new MahoInstallCommand())->setProjectPath($this->project));

    $status = withFakeDocker(fn(): int => $tester->execute([], ['interactive' => false]), exitCode: 1);

    expect($status)->toBe(1)
        ->and($tester->getDisplay())->toContain('Maho installation failed.');
});

it('resolves mariadb as the database host when mysql is not installed', function (): void {
    $other = temporaryProjectPath('corsair-maho-install-mariadb');
    mkdir($other, 0755, true);

    $installer = (new InstallCommand())->setProjectPath($other);
    (new CommandTester($installer))->execute(['--platform' => 'maho', '--with' => 'mariadb'], ['interactive' => false]);
    file_put_contents($other . '/maho', "#!/usr/bin/env php\n");

    $env = (string) file_get_contents($other . '/.env');
    $env = (string) preg_replace('/^DB_HOST=.*/m', 'DB_HOST=127.0.0.1', $env);
    file_put_contents($other . '/.env', $env);

    $tester = new CommandTester((new MahoInstallCommand())->setProjectPath($other));
    $status = $tester->execute([], ['interactive' => false]);

    expect($status)->toBe(0)
        ->and($tester->getDisplay())->toContain("'--db_host=mariadb'");

    removeDirectoryRecursively($other);
});

it('appends a non-default db port to the database host', function (): void {
    $env = (string) file_get_contents($this->project . '/.env');
    $env = (string) preg_replace('/^DB_PORT=.*/m', 'DB_PORT=3307', $env);
    file_put_contents($this->project . '/.env', $env);

    $tester = new CommandTester((new MahoInstallCommand())->setProjectPath($this->project));
    $status = $tester->execute([], ['interactive' => false]);

    expect($status)->toBe(0)
        ->and($tester->getDisplay())->toContain("'--db_host=mysql:3307'");
});
