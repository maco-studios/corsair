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

use Symfony\Component\Process\Process;

function listCorsairCommands(string $project): string
{
    $process = new Process([PHP_BINARY, dirname(__DIR__, 3) . '/bin/corsair', 'list', '--raw'], $project);
    $process->mustRun();

    return $process->getOutput();
}

it('hides maho and magento from the command list when neither is present', function (): void {
    $project = temporaryProjectPath('corsair-shortcuts-neither');
    mkdir($project, 0755, true);

    $output = listCorsairCommands($project);

    expect($output)->not->toContain('maho ')
        ->and($output)->not->toContain('magento ');

    removeDirectoryRecursively($project);
});

it('lists maho only when ./maho exists in the project', function (): void {
    $project = temporaryProjectPath('corsair-shortcuts-maho');
    mkdir($project, 0755, true);
    touch($project . '/maho');

    $output = listCorsairCommands($project);

    expect($output)->toContain('maho ')
        ->and($output)->not->toContain('magento ');

    removeDirectoryRecursively($project);
});

it('lists magento only when bin/magento exists in the project', function (): void {
    $project = temporaryProjectPath('corsair-shortcuts-magento');
    mkdir($project . '/bin', 0755, true);
    touch($project . '/bin/magento');

    $output = listCorsairCommands($project);

    expect($output)->toContain('magento ')
        ->and($output)->not->toContain('maho ');

    removeDirectoryRecursively($project);
});

it('still runs maho and magento directly even when the project has neither marker file', function (): void {
    // The list only hides the shortcut; invoking it directly still reaches
    // MahoCommand/MagentoCommand, which error cleanly on a mismatched
    // project rather than being silently unavailable.
    $project = temporaryProjectPath('corsair-shortcuts-direct');
    mkdir($project, 0755, true);

    $maho = new Process([PHP_BINARY, dirname(__DIR__, 3) . '/bin/corsair', 'maho', 'cache:flush'], $project);
    $maho->run();

    $magento = new Process([PHP_BINARY, dirname(__DIR__, 3) . '/bin/corsair', 'magento', 'cache:flush'], $project);
    $magento->run();

    expect($maho->getExitCode())->toBe(1)
        ->and($maho->getOutput() . $maho->getErrorOutput())->toContain("Couldn't find ./maho")
        ->and($magento->getExitCode())->toBe(1)
        ->and($magento->getOutput() . $magento->getErrorOutput())->toContain("Couldn't find bin/magento");

    removeDirectoryRecursively($project);
});
