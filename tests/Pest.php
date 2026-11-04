<?php

/**
 * Copyright (c) 2026 Marcos "Marcão" Aurelio
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/maco-studios/corsair
 */

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Local test environment
|--------------------------------------------------------------------------
|
| .env (gitignored; see .env.example) holds secrets the real-project e2e
| suite needs but that must never be committed, e.g. the repo.magento.com
| Marketplace keys required to `composer create-project` a real Magento 2
| codebase. Loaded into the environment here so tests can read them with
| getenv().
|
*/

(function (): void {
    $path = dirname(__DIR__) . '/.env';

    if (! is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value, " \t\"'"));
    }
})();

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function temporaryProjectPath(string $prefix): string
{
    $root = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'tmp';

    if (! is_dir($root)) {
        mkdir($root, 0755, true);
    }

    return $root . DIRECTORY_SEPARATOR . $prefix . '-' . bin2hex(random_bytes(4));
}

function corsairBinary(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'corsair';
}

/**
 * Polls $condition until it returns true, or fails with $message once
 * $timeoutSeconds elapses. Used by the real-Docker e2e suite, where
 * containers becoming healthy or a web server answering is never instant.
 */
function waitUntil(callable $condition, int $timeoutSeconds, string $message): void
{
    $deadline = microtime(true) + $timeoutSeconds;

    do {
        if ($condition() === true) {
            return;
        }

        usleep(500_000);
    } while (microtime(true) < $deadline);

    throw new RuntimeException($message);
}

/**
 * Polls `docker compose ps --format json` until every service named in
 * $expectedStates reports that Health (or State, for services with no
 * healthcheck) value.
 *
 * @param  array<string, string>  $expectedStates
 */
function waitForComposeServices(string $projectPath, array $expectedStates, int $timeoutSeconds = 180): void
{
    waitUntil(function () use ($projectPath, $expectedStates): bool {
        $ps = new Symfony\Component\Process\Process(['docker', 'compose', '-f', 'compose.yaml', 'ps', '--format', 'json'], $projectPath);
        $ps->run();

        if (! $ps->isSuccessful()) {
            return false;
        }

        $states = [];

        foreach (array_filter(explode("\n", trim($ps->getOutput()))) as $line) {
            $service = json_decode($line, true);

            if (! is_array($service) || ! isset($service['Service'])) {
                continue;
            }

            // Services without a healthcheck still have a "Health" key, just
            // an empty string — "??" wouldn't fall through to "State" for
            // those, since the key is present.
            $health = (string) ($service['Health'] ?? '');
            $states[$service['Service']] = $health !== '' ? $health : (string) ($service['State'] ?? '');
        }

        foreach ($expectedStates as $service => $expected) {
            if (($states[$service] ?? '') !== $expected) {
                return false;
            }
        }

        return true;
    }, $timeoutSeconds, 'compose services did not reach the expected state in time: ' . json_encode($expectedStates));
}

/**
 * Polls $path on the mapped $port until it answers with $expectedSubstring
 * in the body, confirming nginx + php-fpm are actually serving the app.
 */
function waitForHttp(int $port, string $expectedSubstring = '', int $timeoutSeconds = 60, string $path = '/'): void
{
    waitUntil(function () use ($port, $path, $expectedSubstring): bool {
        $curl = new Symfony\Component\Process\Process(['curl', '--silent', '--fail', '--location', '--max-time', '5', 'http://127.0.0.1:' . $port . $path]);
        $curl->run();

        return $curl->isSuccessful() && ($expectedSubstring === '' || str_contains($curl->getOutput(), $expectedSubstring));
    }, $timeoutSeconds, 'the application container did not answer HTTP requests with the expected content in time');
}

/**
 * A real platform install (Maho's ./maho install, Magento's setup:install,
 * OpenMage's install.php) writes files as whatever user the app container
 * runs as, which doesn't match the host user — e.g. cron/queue loops run
 * as root in their own containers and can leave root-owned, sometimes even
 * chmod-resistant (lock/cache artifacts), files under the bind-mounted
 * project dir. Trying to fix permissions from the host afterward is
 * unreliable, so this deletes the fixture's *contents* as root instead —
 * through a disposable one-off container on the same bind mount, not the
 * app's own service: exec'ing into the app service to do this would also
 * delete compose.yaml/vendor/bin/corsair out from under `corsair down`,
 * which needs them, so call this *after* tearing the stack down, never
 * before.
 */
function purgeDirectoryAsRoot(string $path): void
{
    if (! is_dir($path)) {
        return;
    }

    $purge = new Symfony\Component\Process\Process([
        'docker', 'run', '--rm', '-v', $path . ':/target', 'alpine',
        'sh', '-c', 'rm -rf /target/* /target/.[!.]* /target/..?* 2>/dev/null; true',
    ]);
    $purge->setTimeout(120);
    $purge->run();
}

/**
 * Requires Corsair into a scaffolded project the same way a real consumer
 * would — via `composer require` — except pointed at this checkout through
 * a local path repository, since Corsair isn't published to Packagist yet.
 */
function requireCorsairViaComposer(string $projectPath): void
{
    $repoRoot = dirname(__DIR__);

    (new Symfony\Component\Process\Process(['composer', 'config', 'repositories.corsair', 'path', $repoRoot], $projectPath))
        ->setTimeout(30)
        ->mustRun();

    (new Symfony\Component\Process\Process(['composer', 'require', 'maco-studios/corsair:@dev', '--dev', '--no-interaction'], $projectPath))
        ->setTimeout(120)
        ->mustRun();
}

/**
 * Runs $fn with PHP warnings/notices swallowed, for the host-side cleanup
 * fallback below where permission or missing-file races are expected and
 * already handled by the caller — not a general-purpose suppression tool.
 * "@" alone doesn't do this: PHPUnit/Pest install an error handler that
 * promotes a suppressed warning to a test warning regardless, so the
 * handler itself has to be swapped out for the duration of the call.
 */
function withWarningsSuppressed(callable $fn): mixed
{
    set_error_handler(static fn(): bool => true);

    try {
        return $fn();
    } finally {
        restore_error_handler();
    }
}

/**
 * Puts a fake `docker` binary at the front of PATH for the duration of $fn,
 * so code paths that shell out to real `docker`/`docker compose` commands
 * (building/pulling images, `exec`ing a client, dumping/importing a
 * database) run a real subprocess — and so get real coverage credit for the
 * PHP that launches and reads it — without needing an actual Docker daemon
 * or running containers. The fake just echoes its arguments and exits with
 * $exitCode, which is enough to exercise both the success and failure
 * branches of the code calling it.
 */
function withFakeDocker(callable $fn, int $exitCode = 0): mixed
{
    $dir = temporaryProjectPath('fake-docker-bin');
    mkdir($dir, 0755, true);

    $script = $dir . DIRECTORY_SEPARATOR . 'docker';
    file_put_contents($script, "#!/usr/bin/env bash\necho \"fake docker: \$*\"\nexit {$exitCode}\n");
    chmod($script, 0755);

    // Symfony Process builds its default environment as `$_ENV + getenv()`,
    // so $_ENV's stale PATH (populated once at process start) would win
    // over a plain putenv() change — $_ENV has to be updated directly too.
    $originalPath = (string) getenv('PATH');
    $newPath = $dir . ':' . $originalPath;
    putenv('PATH=' . $newPath);
    $_ENV['PATH'] = $newPath;
    $_SERVER['PATH'] = $newPath;

    try {
        return $fn();
    } finally {
        putenv('PATH=' . $originalPath);
        $_ENV['PATH'] = $originalPath;
        $_SERVER['PATH'] = $originalPath;
        unlink($script);
        rmdir($dir);
    }
}

/**
 * A hand-rolled recursive delete, not SPL's RecursiveDirectoryIterator:
 * that class follows symlinked directories when deciding whether to
 * recurse, and real-project fixtures have one — composer's local path
 * repository installs maco-studios/corsair as a symlink back to this
 * actual checkout. Checking is_link() before is_dir() on every path,
 * including the first call, means a symlinked directory is always
 * unlinked itself rather than ever being descended into.
 *
 * For a real-project e2e fixture, this runs on an already-emptied tree
 * (see purgeFixtureContentsViaContainer()) — the chmod/is_file guards here
 * are a fallback for whatever that step didn't reach, not the primary way
 * root-owned container output gets cleaned up.
 */
function removeDirectoryRecursively(string $path): void
{
    if (is_link($path)) {
        unlink($path);

        return;
    }

    if (! is_dir($path)) {
        if (is_file($path)) {
            withWarningsSuppressed(static function () use ($path): void {
                chmod($path, 0644);
                unlink($path);
            });
        }

        return;
    }

    withWarningsSuppressed(static function () use ($path): void {
        chmod($path, 0755);
    });

    foreach (scandir($path) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        removeDirectoryRecursively($path . DIRECTORY_SEPARATOR . $entry);
    }

    withWarningsSuppressed(static function () use ($path): void {
        rmdir($path);
    });
}
