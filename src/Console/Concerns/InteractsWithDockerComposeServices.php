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

use Symfony\Component\Console\Question\ChoiceQuestion;
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
    protected array $defaultServices = ['mysql', 'redis', 'opensearch'];

    /**
     * @return list<string>
     */
    protected function gatherServicesInteractively(): array
    {
        $question = new ChoiceQuestion(
            'Which services would you like to install?',
            $this->services,
            '0',
        );
        $question->setMultiselect(true);

        /** @var list<string> $services */
        $services = $this->io->askQuestion($question);

        return $services;
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

        if (! array_key_exists('magento.test', $compose['services'])) {
            $this->warn('Couldn\'t find the magento.test service. Make sure you add [' . implode(',', $services) . '] to the depends_on config.');
        } else {
            $dependsOn = $compose['services']['magento.test']['depends_on'] ?? [];
            $compose['services']['magento.test']['depends_on'] = array_values(array_unique(array_merge(
                is_array($dependsOn) ? $dependsOn : [],
                $services,
            )));
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

            if (array_key_exists($service, $compose['volumes'] ?? [])) {
                continue;
            }

            $compose['volumes']['corsair-' . $service] = ['driver' => 'local'];
        }

        if (empty($compose['volumes'])) {
            unset($compose['volumes']);
        }

        $yaml = Yaml::dump(
            $compose,
            10,
            4,
            Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE | Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK,
        );

        $phpVersion = $this->hasOption('php') ? (string) $this->option('php') : '8.3';
        $yaml = str_replace('{{PHP_VERSION}}', $phpVersion, $yaml);

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
        $environment = $this->setEnvValue($environment, 'DB_DATABASE', 'magento');

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
                sprintf('docker compose -f %s pull %s', escapeshellarg($composeFile), implode(' ', array_map('escapeshellarg', $services))),
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
