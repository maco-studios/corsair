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

it('sends php, nginx, and supervisord logs to the container stdout/stderr for every runtime', function () use ($versions, $root): void {
    foreach ($versions as $version) {
        $runtime = $root . '/resources/runtimes/' . $version;

        expect(file_get_contents($runtime . '/conf/php.ini'))
            ->toContain('log_errors = On')
            ->toContain('error_log = /proc/self/fd/2');

        expect(file_get_contents($runtime . '/conf/php-fpm.conf'))
            ->toContain('error_log = /proc/self/fd/2');

        expect(file_get_contents($runtime . '/conf/www.conf'))
            ->toContain('access.log = /proc/self/fd/2');

        foreach (['nginx.conf', 'nginx-maho.conf', 'nginx-openmage.conf'] as $conf) {
            $nginx = file_get_contents($runtime . '/conf/' . $conf);

            expect($nginx)
                ->toContain('error_log /dev/stderr warn;')
                ->toContain('access_log /dev/stdout;')
                ->not->toContain('/var/log/nginx/error.log');
        }

        expect(file_get_contents($runtime . '/supervisord.conf'))
            ->toContain('logfile=/dev/stdout')
            ->toContain('logfile_maxbytes=0')
            ->toContain('stdout_logfile=/dev/stdout')
            ->toContain('stderr_logfile=/dev/stderr')
            ->not->toContain('/var/log/supervisor/supervisord.log');

        expect(file_get_contents($runtime . '/conf/msmtprc'))
            ->toContain('logfile /dev/stdout');
    }
});
