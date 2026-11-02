<?php

/**
 * Copyright (c) 2026 Marcos "Marcão" Aurelio
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/maco-studios/corsair
 */

use Corsair\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;

it('renders the ansi signature on list output', function (): void {
    $application = new Application('Corsair', '0.1.0');
    $application->setAutoExit(false);

    $tester = new ApplicationTester($application);
    $status = $tester->run([], ['decorated' => false]);

    $display = $tester->getDisplay();

    expect($status)->toBe(0)
        ->and($display)->toContain('█')
        ->and($display)->toContain('Corsair 0.1.0');
});

it('renders the signature for --version', function (): void {
    $application = new Application('Corsair', '0.1.0');
    $application->setAutoExit(false);

    $tester = new ApplicationTester($application);
    $status = $tester->run(['--version' => true], ['decorated' => false]);

    $display = $tester->getDisplay();

    expect($status)->toBe(0)
        ->and($display)->toContain('█')
        ->and($display)->toContain('Corsair 0.1.0');
});
