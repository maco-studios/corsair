<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Marcos "Marcão" Aurelio
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/maco-studios/corsair
 */

use Corsair\Console\Application;
use Corsair\Console\AddCommand;
use Corsair\Console\InstallCommand;
use Corsair\Console\PublishCommand;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Process\Process;

function runCorsairList(string $project, array $extraArgs = []): string
{
    $process = new Process([PHP_BINARY, dirname(__DIR__, 3) . '/bin/corsair', 'list', ...$extraArgs], $project);
    $process->mustRun();

    return $process->getOutput();
}

it('groups corsair commands separately from help/list/completion in the default list view', function (): void {
    $project = temporaryProjectPath('corsair-list-grouping');
    mkdir($project, 0755, true);

    $output = runCorsairList($project);

    $builtInSection = substr($output, 0, (int) strpos($output, 'corsair'));
    $corsairSection = substr($output, (int) strpos($output, ' corsair'));

    expect($builtInSection)->toContain('help')
        ->and($builtInSection)->toContain('list')
        ->and($builtInSection)->toContain('completion')
        ->and($builtInSection)->not->toContain('install')
        ->and($builtInSection)->not->toContain('add')
        ->and($builtInSection)->not->toContain('publish')
        ->and($corsairSection)->toContain('install')
        ->and($corsairSection)->toContain('add')
        ->and($corsairSection)->toContain('publish')
        ->and($corsairSection)->not->toContain('help')
        ->and($corsairSection)->not->toContain('completion');

    removeDirectoryRecursively($project);
});

it('does not list the hidden _complete command in the default view', function (): void {
    $project = temporaryProjectPath('corsair-list-hidden');
    mkdir($project, 0755, true);

    $output = runCorsairList($project);

    expect($output)->not->toContain('_complete');

    removeDirectoryRecursively($project);
});

it('falls back to the normal ungrouped output for --raw, --format, --short, and a namespace argument', function (): void {
    $project = temporaryProjectPath('corsair-list-fallback');
    mkdir($project, 0755, true);

    $raw = runCorsairList($project, ['--raw']);
    $short = runCorsairList($project, ['--short']);
    $json = runCorsairList($project, ['--format=json']);

    expect($raw)->not->toContain('<comment>')
        ->and($raw)->not->toContain(' corsair' . "\n")
        ->and($short)->not->toContain(' corsair' . "\n")
        ->and($json)->toContain('"application"');

    removeDirectoryRecursively($project);
});

it('covers the grouped and fallback list paths when run in-process', function (): void {
    $application = new Application('Corsair', '0.1.0');
    $application->addCommands([
        new InstallCommand(),
        new AddCommand(),
        new PublishCommand(),
    ]);
    $application->setAutoExit(false);

    $tester = new ApplicationTester($application);
    $status = $tester->run(['command' => 'list'], ['decorated' => false]);

    expect($status)->toBe(0)
        ->and($tester->getDisplay())->toContain(' corsair');

    $status = $tester->run(['command' => 'list', '--raw' => true], ['decorated' => false]);

    expect($status)->toBe(0)
        ->and($tester->getDisplay())->not->toContain('<comment>');
});
