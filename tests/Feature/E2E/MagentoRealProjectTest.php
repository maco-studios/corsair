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

    $publicKey = getenv('ADOBE_COMMERCE_PUBLIC_KEY');
    $privateKey = getenv('ADOBE_COMMERCE_PRIVATE_KEY');

    if (! is_string($publicKey) || $publicKey === '' || ! is_string($privateKey) || $privateKey === '') {
        $this->markTestSkipped('Set ADOBE_COMMERCE_PUBLIC_KEY and ADOBE_COMMERCE_PRIVATE_KEY in .env to run the real Magento 2 project test (see .env.example).');
    }

    $dockerInfo = new Process(['docker', 'info']);
    $dockerInfo->setTimeout(10);
    $dockerInfo->run();

    if (! $dockerInfo->isSuccessful()) {
        $this->markTestSkipped('Docker is not available or not running.');
    }

    $this->project = temporaryProjectPath('corsair-e2e-magento-real');
    mkdir($this->project, 0755, true);

    // Scoped to this project's composer.json (COMPOSER_AUTH env var), not
    // the host's global composer config — never persists the keys outside
    // this disposable fixture.
    $auth = json_encode(['http-basic' => ['repo.magento.com' => [
        'username' => $publicKey,
        'password' => $privateKey,
    ]]]);

    (new Process([
        'composer', 'create-project',
        '--repository-url=https://repo.magento.com/',
        'magento/project-community-edition', '.',
        '--no-interaction',
    ], $this->project, ['COMPOSER_AUTH' => $auth]))
        ->setTimeout(1800)
        ->mustRun();

    requireCorsairViaComposer($this->project);
});

afterEach(function (): void {
    if (!property_exists($this, 'project') || $this->project === null) {
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

it('scaffolds, installs, and boots a real Magento 2 project through corsair', function (): void {
    $appPort = random_int(20000, 29999);
    $binary = $this->project . '/vendor/bin/corsair';

    expect(is_file($binary))->toBeTrue('composer require did not install vendor/bin/corsair');

    // The latest magento/project-community-edition (unpinned) requires
    // PHP >= 8.5 — composer bakes that requirement into
    // vendor/composer/platform_check.php at create-project time (which ran
    // under the host's own PHP), so the container's runtime must match.
    $install = new Process([PHP_BINARY, $binary, 'install', '--platform=magento', '--with=mysql,redis,opensearch', '--php=8.5'], $this->project);
    $install->setTimeout(1800);
    $install->run();

    expect($install->isSuccessful())->toBeTrue($install->getErrorOutput() . $install->getOutput());

    $env = (string) file_get_contents($this->project . '/.env');
    $env = (string) preg_replace('/^APP_PORT=.*/m', 'APP_PORT=' . $appPort, $env);
    // The app container chowns the bind-mounted project directory to
    // WWWUSER:WWWGROUP on boot (see start-container), which defaults to
    // 1000:1000 — matching this to the actual runner avoids leaving the
    // directory unwritable from the host side afterward.
    $env = (string) preg_replace('/^WWWUSER=.*/m', 'WWWUSER=' . posix_getuid(), $env);
    $env = (string) preg_replace('/^WWWGROUP=.*/m', 'WWWGROUP=' . posix_getgid(), $env);
    file_put_contents($this->project . '/.env', $env);

    $up = new Process([PHP_BINARY, $binary, 'up', '-d'], $this->project);
    $up->setTimeout(1800);
    $up->run();

    expect($up->isSuccessful())->toBeTrue($up->getErrorOutput() . $up->getOutput());

    waitForComposeServices($this->project, [
        'mysql' => 'healthy',
        'redis' => 'healthy',
        'opensearch' => 'healthy',
        'magento.test' => 'running',
    ], 240);

    // Real `bin/magento setup:install` against real mysql + opensearch,
    // through the same `vendor/bin/corsair magento` shortcut a developer
    // would use.
    $setupInstall = new Process([PHP_BINARY, $binary, 'magento', 'setup:install',
        '--base-url=http://127.0.0.1:' . $appPort . '/',
        '--db-host=mysql',
        '--db-name=magento',
        '--db-user=corsair',
        '--db-password=password',
        '--search-engine=opensearch',
        '--opensearch-host=opensearch',
        '--opensearch-port=9200',
        '--opensearch-enable-auth=0',
        '--admin-firstname=Corsair',
        '--admin-lastname=E2E',
        '--admin-email=admin@example.com',
        '--admin-user=admin',
        '--admin-password=Corsair123456!',
        '--language=en_US',
        '--currency=USD',
        '--timezone=UTC',
        '--use-rewrites=1',
        '--backend-frontname=admin',
    ], $this->project);
    $setupInstall->setTimeout(1800);
    $setupInstall->run();

    expect($setupInstall->isSuccessful())->toBeTrue($setupInstall->getErrorOutput() . $setupInstall->getOutput());

    waitForHttp($appPort);

    $storefront = new Process(['curl', '--silent', '--fail', '--location', 'http://127.0.0.1:' . $appPort . '/']);
    $storefront->run();

    expect($storefront->isSuccessful())->toBeTrue()
        ->and(strtolower($storefront->getOutput()))->toContain('<html');

    // vendor/bin/corsair magento really talks to bin/magento in the container.
    $cacheStatus = new Process([PHP_BINARY, $binary, 'magento', 'cache:status'], $this->project);
    $cacheStatus->setTimeout(60);
    $cacheStatus->run();

    expect($cacheStatus->isSuccessful())->toBeTrue($cacheStatus->getErrorOutput() . $cacheStatus->getOutput());
})->group('e2e');
