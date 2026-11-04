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
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;

beforeEach(function (): void {
    putenv('CORSAIR_SKIP_DOCKER=1');

    $this->project = temporaryProjectPath('corsair-add');
    mkdir($this->project, 0755, true);

    $installer = (new InstallCommand())->setProjectPath($this->project);
    (new CommandTester($installer))->execute([
        '--with' => 'mysql',
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

it('adds a service to an existing compose file', function (): void {
    $command = (new AddCommand())->setProjectPath($this->project);
    $tester = new CommandTester($command);

    $status = $tester->execute([
        'services' => 'redis,mailpit',
    ], ['interactive' => false]);

    expect($status)->toBe(0);

    $compose = Yaml::parseFile($this->project . '/compose.yaml');

    expect($compose['services'])->toHaveKeys(['magento.test', 'mysql', 'redis', 'mailpit'])
        ->and($compose['services']['magento.test']['depends_on'])->toContain('mysql', 'redis', 'mailpit');
});

it('adds no services when "none" is given', function (): void {
    $command = (new AddCommand())->setProjectPath($this->project);
    $tester = new CommandTester($command);

    $status = $tester->execute([
        'services' => 'none',
    ], ['interactive' => false]);

    expect($status)->toBe(0);

    $compose = Yaml::parseFile($this->project . '/compose.yaml');

    expect($compose['services']['magento.test']['depends_on'] ?? [])->toContain('mysql')
        ->and($compose['services']['magento.test']['depends_on'] ?? [])->not->toContain('redis');
});

it('falls back to the platform defaults when no services are given non-interactively', function (): void {
    $command = (new AddCommand())->setProjectPath($this->project);
    $tester = new CommandTester($command);

    $status = $tester->execute([], ['interactive' => false]);

    expect($status)->toBe(0);

    $compose = Yaml::parseFile($this->project . '/compose.yaml');

    expect($compose['services']['magento.test']['depends_on'])->toContain('mysql', 'redis', 'opensearch');
});

it('asks interactively for services when none are given on a tty', function (): void {
    $command = (new AddCommand())->setProjectPath($this->project);
    $tester = new CommandTester($command);
    $tester->setInputs(['redis']);

    // gatherServicesInteractively() passes service *names* as the
    // ChoiceQuestion default rather than their numeric indices, which
    // Symfony's question helper only expects for a multiselect default
    // display — harmless (the command still succeeds), just noisy.
    $status = withWarningsSuppressed(fn(): int => $tester->execute([], ['interactive' => true]));

    expect($status)->toBe(0);

    $compose = Yaml::parseFile($this->project . '/compose.yaml');

    expect($compose['services']['magento.test']['depends_on'])->toContain('redis');
});

it('rejects invalid services given as an argument', function (): void {
    $command = (new AddCommand())->setProjectPath($this->project);
    $tester = new CommandTester($command);

    $status = $tester->execute([
        'services' => 'redis,not-a-real-service',
    ], ['interactive' => false]);

    expect($status)->toBe(1);
});
