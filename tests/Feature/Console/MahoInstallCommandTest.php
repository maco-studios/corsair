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
