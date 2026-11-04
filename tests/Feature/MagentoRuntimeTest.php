<?php

/**
 * Copyright (c) 2026 Marcos "Marcão" Aurelio
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/maco-studios/corsair
 */

$versions = ['8.0', '8.1', '8.2', '8.3', '8.4', '8.5'];
$root = dirname(__DIR__, 2);

it('never runs cron or queue consumers inside the web container on every php runtime', function () use ($versions, $root): void {
    $entrypoint = file_get_contents($root . '/resources/runtimes/8.3/start-container');

    expect($entrypoint)
        ->toContain('CORSAIR_PLATFORM:-magento')
        ->not->toContain('cron:run')
        ->not->toContain('cron.php')
        ->not->toContain('queue:consumers')
        ->not->toContain('queue:work');

    foreach ($versions as $version) {
        expect(file_get_contents($root . '/resources/runtimes/' . $version . '/start-container'))->toBe($entrypoint);
    }
});
