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

it('ships the same openmage nginx and entrypoint for every php runtime', function () use ($versions, $root): void {
    $nginx = file_get_contents($root . '/resources/runtimes/8.3/conf/nginx-openmage.conf');
    $entrypoint = file_get_contents($root . '/resources/runtimes/8.3/start-container');

    expect($nginx)
        ->toContain('root /var/www/html;')
        ->toContain('location ~ ^/(app|includes|lib|pkginfo|shell|var)/')
        ->toContain('location ~ ^/api/rest(/|$)')
        ->toContain('location ~ ^/api/(soap|v2_soap|xmlrpc|jsonrpc)(/|$)')
        ->toContain('/api.php?type=rest')
        ->toContain('fastcgi_param HTTP_AUTHORIZATION $http_authorization;')
        ->toContain('fastcgi_pass fastcgi_backend;');

    expect($entrypoint)
        ->toContain('CORSAIR_PLATFORM:-magento')
        ->toContain('nginx-openmage.conf')
        ->toContain('cron.php');

    foreach ($versions as $version) {
        $runtime = $root . '/resources/runtimes/' . $version;

        expect(file_get_contents($runtime . '/conf/nginx-openmage.conf'))->toBe($nginx)
            ->and(file_get_contents($runtime . '/start-container'))->toBe($entrypoint)
            ->and(file_get_contents($runtime . '/Dockerfile'))->toContain('COPY conf/nginx-openmage.conf /opt/corsair/nginx-openmage.conf');
    }
});
