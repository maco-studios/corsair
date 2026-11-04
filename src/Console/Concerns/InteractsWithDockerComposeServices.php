<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Marcos "Marcão" Aurelio
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/maco-studios/corsair
 */

namespace Corsair\Console\Concerns;

use Composer\Semver\Semver;
use Corsair\Prompts\Prompts;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

trait InteractsWithDockerComposeServices
{
    /**
     * @var list<string>
     */
    protected array $composePaths = [
        'compose.yaml',
        'compose.yml',
        'docker-compose.yaml',
        'docker-compose.yml',
    ];

    /**
     * @var list<string>
     */
    protected array $services = [
        'mysql',
        'mariadb',
        'redis',
        'elasticsearch',
        'opensearch',
        'rabbitmq',
        'mailpit',
        'varnish',
    ];

    /**
     * @var list<string>
     */
    protected array $platforms = ['magento', 'maho', 'openmage', 'mageos'];

    /**
     * @var list<string>
     */
    protected array $phpVersions = ['8.0', '8.1', '8.2', '8.3', '8.4', '8.5'];

    protected ?string $resolvedPlatform = null;

    /**
     * @return list<string>
     */
    protected function defaultServices(): array
    {
        return in_array($this->platform(), ['maho', 'openmage'], true)
            ? ['mysql', 'redis']
            : ['mysql', 'redis', 'opensearch'];
    }

    /**
     * @return list<string>
     */
    protected function gatherServicesInteractively(): array
    {
        return Prompts::multiselect(
            label: 'Which services would you like to install?',
            options: array_combine($this->services, $this->services),
            default: $this->defaultServices(),
            validate: function (array $services): ?string {
                if (in_array('mysql', $services, true) || in_array('mariadb', $services, true)) {
                    return null;
                }

                return 'Select mysql or mariadb — the application needs a database to run.';
            },
            hint: 'Use the space bar to select options.',
        );
    }

    /**
     * @param  list<string>  $services
     */
    protected function buildDockerCompose(array $services): void
    {
        $composePath = $this->composePath();

        $compose = file_exists($composePath)
            ? Yaml::parseFile($composePath)
            : Yaml::parse((string) file_get_contents($this->stubPath('compose.stub')));

        if (! is_array($compose)) {
            $compose = [];
        }

        $compose['services'] ??= [];

        $appService = $this->applicationServiceName();

        if ($appService !== 'magento.test' && array_key_exists('magento.test', $compose['services']) && ! array_key_exists($appService, $compose['services'])) {
            $compose['services'] = [$appService => $compose['services']['magento.test']] + $compose['services'];
            unset($compose['services']['magento.test']);
        }

        if (! array_key_exists($appService, $compose['services'])) {
            $this->warn('Couldn\'t find the ' . $appService . ' service. Make sure you add [' . implode(',', $services) . '] to the depends_on config.');
        } else {
            $dependsOn = $compose['services'][$appService]['depends_on'] ?? [];
            $compose['services'][$appService]['depends_on'] = array_values(array_unique(array_merge(
                is_array($dependsOn) ? $dependsOn : [],
                $services,
            )));
            $this->setServiceEnvironment($compose['services'][$appService], 'CORSAIR_PLATFORM', $this->platform());
            $this->ensureSchedulerServices($compose, $appService, $services);
        }

        foreach ($services as $service) {
            if (array_key_exists($service, $compose['services'])) {
                continue;
            }

            $stub = Yaml::parseFile($this->stubPath($service . '.stub'));

            if (is_array($stub) && isset($stub[$service])) {
                $compose['services'][$service] = $stub[$service];
            }
        }

        $volumeServices = ['mysql', 'mariadb', 'redis', 'elasticsearch', 'opensearch', 'rabbitmq'];

        foreach ($services as $service) {
            if (! in_array($service, $volumeServices, true)) {
                continue;
            }

            if (array_key_exists('corsair-' . $service, $compose['volumes'] ?? [])) {
                continue;
            }

            $compose['volumes']['corsair-' . $service] = ['driver' => 'local'];
        }

        if (empty($compose['volumes'])) {
            unset($compose['volumes']);
        }

        if ($this->platform() === 'maho') {
            if (isset($compose['services']['redis']) && is_array($compose['services']['redis'])) {
                $compose['services']['redis']['command'] = 'redis-server --maxmemory-policy volatile-lfu';
            }

            if (isset($compose['services']['mariadb']) && is_array($compose['services']['mariadb'])) {
                $compose['services']['mariadb']['image'] = 'mariadb:11.8';
            }
        }

        $yaml = Yaml::dump(
            $compose,
            10,
            4,
            Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE | Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK,
        );

        $yaml = str_replace('{{PHP_VERSION}}', $this->phpVersion(), $yaml);

        file_put_contents($composePath, $yaml);
    }

    /**
     * @param  list<string>  $services
     */
    protected function replaceEnvVariables(array $services): void
    {
        $envPath = $this->basePath('.env');

        if (! file_exists($envPath)) {
            $example = $this->basePath('.env.example');

            if (file_exists($example)) {
                copy($example, $envPath);
            } else {
                file_put_contents($envPath, $this->defaultEnvContents());
            }
        }

        $environment = (string) file_get_contents($envPath);

        if (in_array('mysql', $services, true) || in_array('mariadb', $services, true)) {
            foreach ([
                '# DB_HOST=127.0.0.1',
                '# DB_PORT=3306',
                '# DB_DATABASE=magento',
                '# DB_USERNAME=root',
                '# DB_PASSWORD=',
            ] as $default) {
                $environment = str_replace($default, substr($default, 2), $environment);
            }
        }

        if (in_array('mysql', $services, true)) {
            $environment = $this->setEnvValue($environment, 'DB_HOST', 'mysql');
            $environment = $this->setEnvValue($environment, 'DB_PORT', '3306');
        } elseif (in_array('mariadb', $services, true)) {
            $environment = $this->setEnvValue($environment, 'DB_HOST', 'mariadb');
            $environment = $this->setEnvValue($environment, 'DB_PORT', '3306');
        }

        $environment = $this->setEnvValue($environment, 'DB_USERNAME', 'corsair');
        $environment = $this->setEnvValue($environment, 'DB_PASSWORD', 'password');
        $environment = $this->setEnvValue($environment, 'DB_DATABASE', match ($this->platform()) {
            'maho' => 'maho',
            'openmage' => 'openmage',
            'mageos' => 'mageos',
            default => 'magento',
        });
        $environment = $this->setEnvValue($environment, 'CORSAIR_PLATFORM', $this->platform());

        if (in_array('redis', $services, true)) {
            $environment = $this->setEnvValue($environment, 'REDIS_HOST', 'redis');
            $environment = $this->setEnvValue($environment, 'REDIS_PORT', '6379');
        }

        if (in_array('elasticsearch', $services, true)) {
            $environment = $this->setEnvValue($environment, 'ELASTICSEARCH_HOST', 'elasticsearch');
            $environment = $this->setEnvValue($environment, 'ELASTICSEARCH_PORT', '9200');
        }

        if (in_array('opensearch', $services, true)) {
            $environment = $this->setEnvValue($environment, 'OPENSEARCH_HOST', 'opensearch');
            $environment = $this->setEnvValue($environment, 'OPENSEARCH_PORT', '9200');
        }

        if (in_array('rabbitmq', $services, true)) {
            $environment = $this->setEnvValue($environment, 'RABBITMQ_HOST', 'rabbitmq');
            $environment = $this->setEnvValue($environment, 'RABBITMQ_PORT', '5672');
            $environment = $this->setEnvValue($environment, 'RABBITMQ_USER', 'corsair');
            $environment = $this->setEnvValue($environment, 'RABBITMQ_PASSWORD', 'password');
            $environment = $this->setEnvValue($environment, 'RABBITMQ_VHOST', '/');
        }

        if (in_array('mailpit', $services, true)) {
            $environment = $this->setEnvValue($environment, 'MAIL_HOST', 'mailpit');
            $environment = $this->setEnvValue($environment, 'MAIL_PORT', '1025');
        }

        if (in_array('varnish', $services, true)) {
            $environment = $this->setEnvValue($environment, 'VARNISH_HOST', 'varnish');
            $environment = $this->setEnvValue($environment, 'VARNISH_PORT', '80');
        }

        file_put_contents($envPath, $environment);
    }

    protected function setEnvValue(string $environment, string $key, string $value): string
    {
        $pattern = '/^' . preg_quote($key, '/') . '=.*/m';

        if (preg_match($pattern, $environment) === 1) {
            return (string) preg_replace($pattern, $key . '=' . $value, $environment);
        }

        return rtrim($environment) . PHP_EOL . $key . '=' . $value . PHP_EOL;
    }

    protected function defaultEnvContents(): string
    {
        return <<<'ENV'
APP_PORT=80
WWWUSER=1000
WWWGROUP=1000
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=magento
DB_USERNAME=root
DB_PASSWORD=
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
ENV;
    }

    /**
     * @param  list<string>  $services
     */
    protected function prepareInstallation(array $services): void
    {
        if (getenv('CORSAIR_SKIP_DOCKER') === '1') {
            return;
        }

        if ($this->runCommands(['docker info > /dev/null 2>&1']) !== 0) {
            return;
        }

        $composeFile = basename($this->composePath());

        if (count($services) > 0) {
            $this->runCommands([
                sprintf('docker compose -f %s pull %s', escapeshellarg($composeFile), implode(' ', array_map(escapeshellarg(...), $services))),
            ]);
        }

        $this->runCommands([
            sprintf('docker compose -f %s build', escapeshellarg($composeFile)),
        ]);
    }

    /**
     * @param  list<string>  $commands
     */
    protected function runCommands(array $commands): int
    {
        $process = Process::fromShellCommandline(implode(' && ', $commands), $this->basePath(), null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            try {
                $process->setTty(true);
            } catch (\RuntimeException $e) {
                $this->output->writeln('  <bg=yellow;fg=black> WARN </> ' . $e->getMessage() . PHP_EOL);
            }
        }

        return $process->run(function (string $type, string $line): void {
            $this->output->write('    ' . $line);
        });
    }

    protected function platform(): string
    {
        if ($this->hasOption('platform')) {
            $platform = $this->option('platform');

            if (is_string($platform) && $platform !== '') {
                return strtolower($platform);
            }
        }

        if ($this->resolvedPlatform !== null) {
            return $this->resolvedPlatform;
        }

        $composePath = $this->composePath();

        if (file_exists($composePath)) {
            $compose = Yaml::parseFile($composePath);

            if (is_array($compose)) {
                $services = $compose['services'] ?? [];

                if (is_array($services)) {
                    foreach ($services as $service) {
                        if (! is_array($service)) {
                            continue;
                        }

                        $value = $this->environmentValue($service['environment'] ?? null, 'CORSAIR_PLATFORM');

                        if (is_string($value) && $value !== '') {
                            return $this->resolvedPlatform = strtolower($value);
                        }
                    }

                    if (array_key_exists('maho.test', $services)) {
                        return $this->resolvedPlatform = 'maho';
                    }
                }
            }
        }

        $platform = $this->platformFromComposerJson() ?? $this->askPlatform();

        return $this->resolvedPlatform = $platform;
    }

    protected function platformFromComposerJson(): ?string
    {
        $composerPath = $this->basePath('composer.json');

        if (! file_exists($composerPath)) {
            return null;
        }

        $composer = json_decode((string) file_get_contents($composerPath), true);

        if (! is_array($composer)) {
            return null;
        }

        $require = is_array($composer['require'] ?? null) ? $composer['require'] : [];
        $requireDev = is_array($composer['require-dev'] ?? null) ? $composer['require-dev'] : [];

        foreach (array_keys($require + $requireDev) as $package) {
            if (! is_string($package)) {
                continue;
            }

            if (str_starts_with($package, 'mahocommerce/')) {
                return 'maho';
            }

            if (str_starts_with($package, 'openmage/')) {
                return 'openmage';
            }

            if (str_starts_with($package, 'mage-os/')) {
                return 'mageos';
            }

            if (str_starts_with($package, 'magento/')) {
                return 'magento';
            }
        }

        return null;
    }

    protected function askPlatform(): string
    {
        if (! isset($this->input) || ! $this->input->isInteractive()) {
            return 'magento';
        }

        /** @var string $platform */
        $platform = Prompts::select(
            label: 'Which platform is this application (couldn\'t detect it from composer.json)?',
            options: array_combine($this->platforms, $this->platforms),
            default: 'magento',
        );

        return $platform;
    }

    protected function phpVersion(): string
    {
        if ($this->hasOption('php')) {
            $php = $this->option('php');

            if (is_string($php) && $php !== '') {
                return $php;
            }
        }

        return $this->phpVersionFromComposer()
            ?? ($this->platform() === 'maho' ? '8.5' : '8.3');
    }

    /**
     * The newest supported runtime that satisfies the project's effective
     * PHP requirement. `config.platform.php` (an explicit override) wins
     * outright, matching how Composer itself treats it — it replaces
     * detection rather than narrowing it further. Otherwise, every PHP
     * constraint has to be satisfied at once: the root `require.php` *and*
     * every package locked in composer.lock that declares its own `php`
     * requirement, since that combined set is exactly what Composer
     * aggregates into the `vendor/composer/platform_check.php` it runs on
     * every request — the thing that actually produced the "Composer
     * detected issues in your platform" error this method exists to avoid.
     * A root constraint alone can under-report it: a project's own
     * composer.json can allow a wide range while a dependency it locked
     * narrows that down behind the scenes.
     *
     * Returns null when neither file has a usable PHP constraint, or when
     * none of our runtimes satisfy the combined set — the caller falls back
     * to the platform default either way.
     */
    protected function phpVersionFromComposer(): ?string
    {
        $composer = $this->readJsonFile('composer.json');

        $config = is_array($composer['config'] ?? null) ? $composer['config'] : [];
        $platform = is_array($config['platform'] ?? null) ? $config['platform'] : [];
        $override = $platform['php'] ?? null;

        if (is_string($override) && trim($override) !== '') {
            return $this->newestSatisfying([$override]);
        }

        $require = is_array($composer['require'] ?? null) ? $composer['require'] : [];
        $constraints = [];

        if (is_string($require['php'] ?? null) && trim($require['php']) !== '') {
            $constraints[] = $require['php'];
        }

        $lock = $this->readJsonFile('composer.lock');
        $packages = array_merge(
            is_array($lock['packages'] ?? null) ? $lock['packages'] : [],
            is_array($lock['packages-dev'] ?? null) ? $lock['packages-dev'] : [],
        );

        foreach ($packages as $package) {
            if (! is_array($package)) {
                continue;
            }

            $packageRequire = is_array($package['require'] ?? null) ? $package['require'] : [];
            $packageConstraint = $packageRequire['php'] ?? null;

            if (is_string($packageConstraint) && trim($packageConstraint) !== '') {
                $constraints[] = $packageConstraint;
            }
        }

        if ($constraints === []) {
            return null;
        }

        return $this->newestSatisfying($constraints);
    }

    /**
     * The newest of our supported runtimes that satisfies every given
     * constraint simultaneously, or null if none do. A constraint Semver
     * can't parse is skipped rather than failing the whole lookup — package
     * metadata out in the wild occasionally has a malformed `php` entry.
     *
     * @param  list<string>  $constraints
     */
    protected function newestSatisfying(array $constraints): ?string
    {
        foreach (array_reverse($this->phpVersions) as $version) {
            $satisfiesAll = true;

            foreach ($constraints as $constraint) {
                try {
                    if (! Semver::satisfies($version . '.0', $constraint)) {
                        $satisfiesAll = false;

                        break;
                    }
                } catch (\UnexpectedValueException) {
                    continue;
                }
            }

            if ($satisfiesAll) {
                return $version;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function readJsonFile(string $filename): array
    {
        $path = $this->basePath($filename);

        if (! file_exists($path)) {
            return [];
        }

        $contents = json_decode((string) file_get_contents($path), true);

        return is_array($contents) ? $contents : [];
    }

    protected function applicationServiceName(): string
    {
        $composePath = $this->composePath();

        if (file_exists($composePath)) {
            $compose = Yaml::parseFile($composePath);

            if (is_array($compose)) {
                $detected = $this->applicationServiceFromCompose($compose);

                if ($detected !== null) {
                    return $detected;
                }
            }
        }

        return match ($this->platform()) {
            'maho' => 'maho.test',
            'openmage' => 'openmage.test',
            'mageos' => 'mageos.test',
            default => 'magento.test',
        };
    }

    /**
     * @param  array<string, mixed>  $compose
     */
    protected function applicationServiceFromCompose(array $compose): ?string
    {
        $services = $compose['services'] ?? [];

        if (! is_array($services)) {
            return null;
        }

        foreach (['maho.test', 'openmage.test', 'mageos.test', 'magento.test'] as $name) {
            if (array_key_exists($name, $services)) {
                return $name;
            }
        }

        return null;
    }

    /**
     * The cron/queue loops Corsair runs as the CMD of their own dedicated
     * containers, keyed by the service name. start-container execs this
     * command as-is (dropping privileges to WWWUSER when needed), so no
     * extra script has to be baked into the image for it.
     *
     * @return array<string, string>
     */
    protected function schedulerProcesses(): array
    {
        return match ($this->platform()) {
            'maho' => [
                'scheduler' => 'while true; do '
                    . 'if [ -f ./maho ]; then php ./maho cron:run default || true; php ./maho cron:run always || true; fi; '
                    . 'sleep 60; '
                    . 'done',
                'queue' => 'while true; do '
                    . 'if [ -f ./maho ]; then php ./maho queue:work --exclusive || true; fi; '
                    . 'sleep 5; '
                    . 'done',
            ],
            'openmage' => [
                'scheduler' => 'while true; do '
                    . 'if [ -f ./cron.php ]; then php ./cron.php || true; fi; '
                    . 'sleep 60; '
                    . 'done',
            ],
            default => [
                'scheduler' => 'while true; do '
                    . 'if [ -f ./bin/magento ]; then php bin/magento cron:run || true; fi; '
                    . 'sleep 60; '
                    . 'done',
                'queue' => 'while true; do '
                    . 'if [ -f ./bin/magento ]; then '
                    . 'for consumer in $(php bin/magento queue:consumers:list 2>/dev/null); do '
                    . 'php bin/magento queue:consumers:start "$consumer" --max-messages=10000 & '
                    . 'done; wait; '
                    . 'fi; '
                    . 'sleep 60; '
                    . 'done',
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $compose
     * @param  list<string>  $services
     */
    protected function ensureSchedulerServices(array &$compose, string $appService, array $services): void
    {
        foreach ($this->schedulerProcesses() as $name => $script) {
            $service = $compose['services'][$name] ?? null;

            if (! is_array($service)) {
                $service = $compose['services'][$appService];
                unset($service['ports']);
            }

            $dependsOn = $service['depends_on'] ?? [];
            $service['depends_on'] = array_values(array_unique(array_merge(
                is_array($dependsOn) ? $dependsOn : [],
                $services,
            )));

            $service['command'] = ['bash', '-c', $script];
            $service['restart'] = 'unless-stopped';

            $this->setServiceEnvironment($service, 'CORSAIR_PLATFORM', $this->platform());

            $compose['services'][$name] = $service;
        }
    }

    /**
     * @param  array<string, mixed>  $service
     */
    protected function setServiceEnvironment(array &$service, string $key, string $value): void
    {
        $environment = $service['environment'] ?? [];

        if (! is_array($environment) || array_is_list($environment)) {
            return;
        }

        $environment[$key] = $value;
        $service['environment'] = $environment;
    }

    /**
     * @param  list<string>  $arguments
     */
    protected function dockerComposeExec(array $arguments, ?string $service = null, bool $disableTty = false): string
    {
        // `docker compose exec` defaults to root — the Dockerfile has no
        // USER directive, since the entrypoint itself drops privileges for
        // the long-running processes (nginx/php-fpm/the scheduler loop) via
        // gosu. Without --user here, every ad-hoc command (shell, npm,
        // composer, maho, magento) would run as root while php-fpm serves
        // requests as WWWUSER, so anything either side writes (e.g.
        // Magento's lazily-generated DI interceptors under generated/code)
        // ends up owned by whichever ran first, blocking the other. Only
        // do this for the application container itself ($service === null)
        // — mysql/mariadb/redis have their own images' users and no
        // WWWUSER concept.
        $userFlag = $service === null ? '--user ' . escapeshellarg($this->containerUser()) . ' ' : '';

        return sprintf(
            'docker compose exec %s%s%s %s',
            $userFlag,
            $disableTty ? '-T ' : '',
            escapeshellarg($service ?? $this->applicationServiceName()),
            implode(' ', array_map(escapeshellarg(...), $arguments)),
        );
    }

    /**
     * The uid:gid the application container's web server runs as, so ad-hoc
     * commands exec'd into it don't create root-owned files alongside it.
     */
    protected function containerUser(): string
    {
        $env = $this->envVariables();

        return ($env['WWWUSER'] ?? '1000') . ':' . ($env['WWWGROUP'] ?? '1000');
    }

    /**
     * @return array<string, string>
     */
    protected function envVariables(): array
    {
        $path = $this->basePath('.env');

        if (! is_file($path)) {
            return [];
        }

        $variables = [];

        foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $variables[$key] = trim($value, " \t\"'");
        }

        return $variables;
    }

    protected function stackHasService(string $service): bool
    {
        if (! is_file($this->composePath())) {
            return false;
        }

        $compose = Yaml::parseFile($this->composePath());

        return is_array($compose)
            && is_array($compose['services'] ?? null)
            && array_key_exists($service, $compose['services']);
    }

    /**
     * The installed MySQL-compatible database service, if any. `mysql` wins
     * when both are present.
     */
    protected function databaseEngine(): ?string
    {
        if ($this->stackHasService('mysql')) {
            return 'mysql';
        }

        if ($this->stackHasService('mariadb')) {
            return 'mariadb';
        }

        return null;
    }

    protected function environmentValue(mixed $environment, string $key): ?string
    {
        if (! is_array($environment)) {
            return null;
        }

        if (array_key_exists($key, $environment) && is_scalar($environment[$key])) {
            return (string) $environment[$key];
        }

        foreach ($environment as $item) {
            if (is_string($item) && str_starts_with($item, $key . '=')) {
                return substr($item, strlen($key) + 1);
            }
        }

        return null;
    }

    protected function composePath(): string
    {
        foreach ($this->composePaths as $path) {
            $absolute = $this->basePath($path);

            if (file_exists($absolute)) {
                return $absolute;
            }
        }

        return $this->basePath('compose.yaml');
    }

    protected function stubPath(string $stub): string
    {
        return dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . $stub;
    }

    protected function packagePath(string $path = ''): string
    {
        $root = dirname(__DIR__, 3);

        return $path === ''
            ? $root
            : $root . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }
}
