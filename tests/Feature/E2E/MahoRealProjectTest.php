<?php

/**
 * Copyright (c) 2026 Marcos "Marcão" Aurelio
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/maco-studios/corsair
 */

use Symfony\Component\Process\Process;

beforeEach(function (): void {
    if (getenv('CORSAIR_E2E') !== '1') {
        $this->markTestSkipped('Set CORSAIR_E2E=1 to run the real Docker end-to-end suite (scaffolds a real project, builds images, and starts containers; can take several minutes on a cold cache).');
    }

    $dockerInfo = new Process(['docker', 'info']);
    $dockerInfo->setTimeout(10);
    $dockerInfo->run();

    if (! $dockerInfo->isSuccessful()) {
        $this->markTestSkipped('Docker is not available or not running.');
    }

    $this->project = temporaryProjectPath('corsair-e2e-maho-real');
    mkdir($this->project, 0755, true);

    (new Process(['composer', 'create-project', 'mahocommerce/maho-starter', '.', '--no-interaction'], $this->project))
        ->setTimeout(300)
        ->mustRun();

    requireCorsairViaComposer($this->project);
});

afterEach(function (): void {
    if (! isset($this->project)) {
        return;
    }

    $binary = $this->project . '/vendor/bin/corsair';

    if (is_file($binary)) {
        (new Process([PHP_BINARY, $binary, 'down', '-v', '--remove-orphans'], $this->project))
            ->setTimeout(120)
            ->run();
    }

    purgeDirectoryAsRoot($this->project);
    removeDirectoryRecursively($this->project);
});

it('scaffolds, installs, and boots a real MahoCommerce project through corsair', function (): void {
    $appPort = random_int(20000, 29999);
    $binary = $this->project . '/vendor/bin/corsair';

    expect(is_file($binary))->toBeTrue('composer require did not install vendor/bin/corsair');

    $install = new Process([PHP_BINARY, $binary, 'install', '--platform=maho', '--with=mysql,redis', '--php=8.5'], $this->project);
    $install->setTimeout(1800);
    $install->run();

    expect($install->isSuccessful())->toBeTrue($install->getErrorOutput() . $install->getOutput());

    $env = (string) file_get_contents($this->project . '/.env');
    $env = (string) preg_replace('/^APP_PORT=.*/m', 'APP_PORT=' . $appPort, $env);
    file_put_contents($this->project . '/.env', $env);

    $up = new Process([PHP_BINARY, $binary, 'up', '-d'], $this->project);
    $up->setTimeout(1800);
    $up->run();

    expect($up->isSuccessful())->toBeTrue($up->getErrorOutput() . $up->getOutput());

    waitForComposeServices($this->project, [
        'mysql' => 'healthy',
        'redis' => 'healthy',
        'maho.test' => 'running',
    ]);

    // Real `./maho install` against the real mysql container, driven
    // manually through `vendor/bin/corsair maho` the same way a developer
    // would — Corsair doesn't wrap the application installer itself for
    // any platform, Maho included.
    $url = 'http://localhost:' . $appPort . '/';
    $mahoInstall = new Process([
        PHP_BINARY, $binary, 'maho', 'install',
        '--license_agreement_accepted=yes',
        '--locale=en_US',
        '--timezone=UTC',
        '--default_currency=USD',
        '--db_host=mysql',
        '--db_name=maho',
        '--db_user=corsair',
        '--db_pass=password',
        '--url=' . $url,
        '--secure_base_url=' . $url,
        '--use_secure=0',
        '--use_secure_admin=0',
        '--admin_firstname=Store',
        '--admin_lastname=Admin',
        '--admin_email=admin@example.com',
        '--admin_username=admin',
        '--admin_password=Corsair123456!',
    ], $this->project);
    $mahoInstall->setTimeout(900);
    $mahoInstall->run();

    expect($mahoInstall->isSuccessful())->toBeTrue($mahoInstall->getErrorOutput() . $mahoInstall->getOutput());

    $reindex = new Process([PHP_BINARY, $binary, 'maho', 'index:reindex:all'], $this->project);
    $reindex->setTimeout(300);
    $reindex->run();

    expect($reindex->isSuccessful())->toBeTrue($reindex->getErrorOutput() . $reindex->getOutput());

    // The storefront actually boots and responds with HTML, not a stub.
    waitForHttp($appPort);

    $storefront = new Process(['curl', '--silent', '--fail', '--location', 'http://127.0.0.1:' . $appPort . '/']);
    $storefront->run();

    expect($storefront->isSuccessful())->toBeTrue()
        ->and(strtolower($storefront->getOutput()))->toContain('<html');

    // vendor/bin/corsair maho really talks to ./maho inside the container.
    $cacheFlush = new Process([PHP_BINARY, $binary, 'maho', 'cache:flush'], $this->project);
    $cacheFlush->setTimeout(60);
    $cacheFlush->run();

    expect($cacheFlush->isSuccessful())->toBeTrue($cacheFlush->getErrorOutput() . $cacheFlush->getOutput());
})->group('e2e');
