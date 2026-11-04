<?php

/**
 * Copyright (c) 2026 Marcos "Marcão" Aurelio
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/maco-studios/corsair
 */

use Corsair\Console\InstallCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\Process;

beforeEach(function (): void {
    if (getenv('CORSAIR_E2E') !== '1') {
        $this->markTestSkipped('Set CORSAIR_E2E=1 to run the real Docker end-to-end stack test (builds images and starts containers; can take several minutes on a cold cache).');
    }

    $dockerInfo = new Process(['docker', 'info']);
    $dockerInfo->setTimeout(10);
    $dockerInfo->run();

    if (! $dockerInfo->isSuccessful()) {
        $this->markTestSkipped('Docker is not available or not running.');
    }

    $this->project = temporaryProjectPath('corsair-e2e-magento');
    mkdir($this->project, 0755, true);

    // Mirrors how a real consumer project has the package under
    // vendor/maco-studios/corsair, since compose.yaml's build context and
    // the mysql init script volume are both relative paths through it.
    mkdir($this->project . '/vendor/maco-studios', 0755, true);
    symlink(dirname(__DIR__, 3), $this->project . '/vendor/maco-studios/corsair');

    file_put_contents($this->project . '/composer.json', json_encode([
        'name' => 'corsair/e2e-fixture',
        'require' => ['magento/product-community-edition' => '2.4.*'],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    mkdir($this->project . '/pub', 0755, true);
    file_put_contents($this->project . '/pub/index.php', '<?php echo "corsair-e2e-ok";');
});

afterEach(function (): void {
    if (!property_exists($this, 'project') || $this->project === null) {
        return;
    }

    (new Process([PHP_BINARY, corsairBinary(), 'down', '-v', '--remove-orphans'], $this->project))
        ->setTimeout(120)
        ->run();

    removeDirectoryRecursively($this->project);
});

it('installs, boots, and tears down a real magento docker stack', function (): void {
    $appPort = random_int(20000, 29999);

    $installer = (new InstallCommand())->setProjectPath($this->project);
    $status = (new CommandTester($installer))->execute([
        '--platform' => 'magento',
        '--with' => 'mysql,redis',
        '--php' => '8.3',
    ], ['interactive' => false]);

    expect($status)->toBe(0);
    expect(is_file($this->project . '/compose.yaml'))->toBeTrue();

    $env = (string) file_get_contents($this->project . '/.env');
    $env = (string) preg_replace('/^APP_PORT=.*/m', 'APP_PORT=' . $appPort, $env);
    // The app container chowns the bind-mounted project directory to
    // WWWUSER:WWWGROUP on boot (see start-container), which defaults to
    // 1000:1000 — matching this to the actual runner matters here, since
    // db:dump below writes to this same directory from the host side.
    $env = (string) preg_replace('/^WWWUSER=.*/m', 'WWWUSER=' . posix_getuid(), $env);
    $env = (string) preg_replace('/^WWWGROUP=.*/m', 'WWWGROUP=' . posix_getgid(), $env);
    $env .= PHP_EOL . 'FORWARD_DB_PORT=' . random_int(30000, 39999) . PHP_EOL . 'FORWARD_REDIS_PORT=' . random_int(40000, 49999) . PHP_EOL;
    file_put_contents($this->project . '/.env', $env);

    // Real `docker compose up -d`, through the same alias devs use.
    $up = new Process([PHP_BINARY, corsairBinary(), 'up', '-d'], $this->project);
    $up->setTimeout(1800);
    $up->run();

    expect($up->isSuccessful())->toBeTrue($up->getErrorOutput() . $up->getOutput());

    waitForComposeServices($this->project, [
        'mysql' => 'healthy',
        'redis' => 'healthy',
        'magento.test' => 'running',
    ]);

    // The app container is really serving HTTP through nginx + php-fpm.
    waitForHttp($appPort, 'corsair-e2e-ok');

    // db:dump really talks to mysql, not a CORSAIR_SKIP_DOCKER stub.
    $dump = new Process([PHP_BINARY, corsairBinary(), 'db:dump', 'dump.sql'], $this->project);
    $dump->setTimeout(60);
    $dump->run();

    expect($dump->isSuccessful())->toBeTrue($dump->getErrorOutput())
        ->and(file_get_contents($this->project . '/dump.sql'))->toContain('-- MySQL dump');

    // Redis is reachable too.
    $redisPing = new Process(['docker', 'compose', '-f', 'compose.yaml', 'exec', '-T', 'redis', 'redis-cli', 'ping'], $this->project);
    $redisPing->run();

    expect($redisPing->isSuccessful())->toBeTrue()
        ->and(trim($redisPing->getOutput()))->toBe('PONG');
})->group('e2e');
