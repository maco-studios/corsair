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

    $this->project = temporaryProjectPath('corsair-e2e-openmage-real');
    mkdir($this->project, 0755, true);

    (new Process(['composer', 'init', '--no-interaction', '--name=corsair/e2e-openmage-fixture'], $this->project))
        ->setTimeout(30)
        ->mustRun();

    // extra.magento-root-dir is "." so OpenMage lands directly in the
    // project root — the classic flat layout Corsair's nginx-openmage.conf
    // expects (index.php, app/, includes/, lib/, shell/ at the top level).
    //
    // extra.patches applies a real bug fix in openmage/magento-lts itself
    // (not a Corsair/Docker issue): Mage_Core_Model_Config::getCacheSaveLock()
    // and releaseCacheSaveLock() call Mage::getSingleton('core/resource')
    // ->getConnection('core_write')->fetchOne(...) unconditionally whenever
    // useCache('config') is true — which it is by default even before
    // install.php has ever run. With no local.xml yet, getConnection()
    // returns false (no DB configured), and calling ->fetchOne() on false
    // is a fatal error, so install.php crashes before it ever creates
    // app/etc/local.xml or the database schema. The same class already
    // guards other cache code with Mage::isInstalled() elsewhere — this
    // patch adds that same guard to the two lock methods. Applied via
    // cweagans/composer-patches, which openmage/magento-lts already pulls
    // in transitively (see "OpenMage's installer packages ship Composer
    // plugins" below), before requiring openmage/magento-lts so it's in
    // effect for its own install event.
    // vaimo/composer-patches resolves local patch paths relative to the
    // disposable project, so this must stay project-relative rather than
    // being passed as an absolute repository path.
    $patch = '../../../resources/patches/openmage-install-cache-lock-guard.patch';

    foreach ([
        ['config', '--json', 'extra.enable-patching', 'true'],
        ['config', 'extra.magento-core-package-type', 'magento-source'],
        ['config', 'extra.magento-root-dir', '.'],
        ['config', 'platform.php', '8.3'],
        // OpenMage 20 currently depends on flowjs/flowjs dev-master. Keep
        // the fixture permissive enough to resolve that transitive package,
        // while still preferring stable releases everywhere else.
        ['config', 'minimum-stability', 'dev'],
        ['config', 'prefer-stable', 'true'],
        ['config', '--json', 'extra.patches', json_encode([
            'openmage/magento-lts' => [
                'Guard getCacheSaveLock/releaseCacheSaveLock with Mage::isInstalled() so install.php does not crash before local.xml exists' => $patch,
            ],
        ])],
        // OpenMage's installer packages ship Composer plugins; this is a
        // disposable fixture, so trust them all rather than naming each one.
        ['config', '--no-plugins', 'allow-plugins', 'true'],
    ] as $args) {
        (new Process(array_merge(['composer'], $args), $this->project))->setTimeout(30)->mustRun();
    }

    (new Process(['composer', 'require', 'aydin-hassan/magento-core-composer-installer:~2.1.0', '--no-interaction'], $this->project))
        ->setTimeout(120)
        ->mustRun();

    // 20.18.2 is the latest OpenMage 20 release whose bundled Composer
    // patches are compatible with its dependency graph.  20.19.0 currently
    // republishes stale ZF1 patches that cannot apply to zf1-future 1.26.
    (new Process(['composer', 'require', 'openmage/magento-lts:20.18.2', '--no-interaction'], $this->project))
        ->setTimeout(300)
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

it('scaffolds, installs, and boots a real OpenMage project through corsair', function (): void {
    $appPort = random_int(20000, 29999);
    $binary = $this->project . '/vendor/bin/corsair';

    expect(is_file($binary))->toBeTrue('composer require did not install vendor/bin/corsair');
    expect(is_file($this->project . '/install.php'))->toBeTrue('OpenMage did not install into the project root (check extra.magento-root-dir)');

    $install = new Process([PHP_BINARY, $binary, 'install', '--platform=openmage', '--with=mysql,redis', '--php=8.3'], $this->project);
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
    $env .= PHP_EOL . 'FORWARD_DB_PORT=' . random_int(30000, 39999) . PHP_EOL;
    $env .= 'FORWARD_REDIS_PORT=' . random_int(40000, 49999) . PHP_EOL;
    file_put_contents($this->project . '/.env', $env);

    $up = new Process([PHP_BINARY, $binary, 'up', '-d'], $this->project);
    $up->setTimeout(1800);
    $up->run();

    expect($up->isSuccessful())->toBeTrue($up->getErrorOutput() . $up->getOutput());

    waitForComposeServices($this->project, [
        'mysql' => 'healthy',
        'redis' => 'healthy',
        'openmage.test' => 'running',
    ]);

    // OpenMage ships its own CLI installer (install.php); Corsair has no
    // dedicated shortcut for it (see docs/how-to/install-on-openmage.md),
    // so run it the documented way: through the app container's shell.
    $installPhp = implode(' ', array_map(escapeshellarg(...), [
        'php', 'install.php',
        '--license_agreement_accepted', 'yes',
        '--locale', 'en_US',
        '--timezone', 'UTC',
        '--default_currency', 'USD',
        '--db_host', 'mysql',
        '--db_name', 'openmage',
        '--db_user', 'corsair',
        '--db_pass', 'password',
        // Must be the host-reachable address the test later curls, not the
        // internal openmage.test hostname — OpenMage redirects to whatever
        // base URL it's configured with, and the host can't resolve the
        // Docker-internal service name.
        '--url', 'http://127.0.0.1:' . $appPort . '/',
        '--skip_url_validation', 'yes',
        '--use_rewrites', 'yes',
        '--use_secure', 'no',
        '--secure_base_url', '',
        '--use_secure_admin', 'no',
        '--admin_firstname', 'Corsair',
        '--admin_lastname', 'E2E',
        '--admin_email', 'admin@example.com',
        '--admin_username', 'admin',
        '--admin_password', 'Corsair123456!',
    ]));

    $openMageInstall = new Process(
        ['docker', 'compose', '-f', 'compose.yaml', 'exec', '-T', 'openmage.test', 'bash', '-c', $installPhp],
        $this->project,
    );
    $openMageInstall->setTimeout(900);
    $openMageInstall->run();

    expect($openMageInstall->isSuccessful())->toBeTrue($openMageInstall->getErrorOutput() . $openMageInstall->getOutput())
        ->and($openMageInstall->getOutput())->toContain('SUCCESS');

    waitForHttp($appPort);

    $storefront = new Process(['curl', '--silent', '--fail', '--location', 'http://127.0.0.1:' . $appPort . '/']);
    $storefront->run();

    expect($storefront->isSuccessful())->toBeTrue()
        ->and(strtolower($storefront->getOutput()))->toContain('<html');

    // OpenMage's own maintenance scripts (docs/how-to/install-on-openmage.md)
    // really run post-install, through the same exec path `corsair shell`
    // uses — just non-interactively (-T) since there's no TTY here.
    $reindex = new Process(
        ['docker', 'compose', '-f', 'compose.yaml', 'exec', '-T', 'openmage.test', 'php', 'shell/indexer.php', 'reindexall'],
        $this->project,
    );
    $reindex->setTimeout(120);
    $reindex->run();

    expect($reindex->isSuccessful())->toBeTrue($reindex->getErrorOutput() . $reindex->getOutput());
})->group('e2e');
