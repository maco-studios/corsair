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

it('ships the same maho nginx and entrypoint for every php runtime', function () use ($versions, $root): void {
    $nginx = file_get_contents($root . '/resources/runtimes/8.5/conf/nginx-maho.conf');
    $entrypoint = file_get_contents($root . '/resources/runtimes/8.5/start-container');

    expect($nginx)
        ->toContain('root /var/www/html/public;')
        ->toContain('location ~ ^/api/rest/v2(/|$)')
        ->toContain('location ~ ^/api/rest(/|$)')
        ->toContain('location ~ ^/api/(soap|v2_soap|xmlrpc|jsonrpc)(/|$)')
        ->toContain('/rest.php$is_args$args')
        ->toContain('/api.php?type=rest')
        ->toContain('location ~ ^/media/(customer|customer_address|downloadable|custom_options)/')
        ->toContain('location ~ ^/media/.+/cache/[0-9a-f]{12}/[0-9]+/')
        ->toContain('fastcgi_param HTTP_AUTHORIZATION $http_authorization;')
        ->toContain('fastcgi_pass fastcgi_backend;')
        ->not->toContain('\.md');

    expect($entrypoint)
        ->toContain('CORSAIR_PLATFORM:-magento')
        ->toContain('nginx-maho.conf')
        ->not->toContain('cron:run')
        ->not->toContain('queue:work');

    foreach ($versions as $version) {
        $runtime = $root . '/resources/runtimes/' . $version;

        expect(file_get_contents($runtime . '/conf/nginx-maho.conf'))->toBe($nginx)
            ->and(file_get_contents($runtime . '/start-container'))->toBe($entrypoint)
            ->and(file_get_contents($runtime . '/Dockerfile'))->toContain('libcurl4-openssl-dev')
            ->and(file_get_contents($runtime . '/Dockerfile'))->toContain("\n    curl \\\n")
            ->and(file_get_contents($runtime . '/Dockerfile'))->toContain('COPY conf/nginx-maho.conf /opt/corsair/nginx-maho.conf');
    }
});
