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

namespace Corsair\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'maho:install', description: 'Install MahoCommerce with the database credentials from .env')]
class MahoInstallCommand extends Command
{
    use Concerns\InteractsWithDockerComposeServices;

    protected function configure(): void
    {
        $this
            ->addOption('url', null, InputOption::VALUE_REQUIRED, 'Store URL. A trailing slash is added when missing')
            ->addOption('locale', null, InputOption::VALUE_REQUIRED, 'Store locale', 'en_US')
            ->addOption('timezone', null, InputOption::VALUE_REQUIRED, 'Store timezone', 'UTC')
            ->addOption('currency', null, InputOption::VALUE_REQUIRED, 'Default currency', 'USD')
            ->addOption('admin-firstname', null, InputOption::VALUE_REQUIRED, 'Admin first name', 'Store')
            ->addOption('admin-lastname', null, InputOption::VALUE_REQUIRED, 'Admin last name', 'Admin')
            ->addOption('admin-email', null, InputOption::VALUE_REQUIRED, 'Admin email', 'admin@example.com')
            ->addOption('admin-username', null, InputOption::VALUE_REQUIRED, 'Admin username', 'admin')
            ->addOption('admin-password', null, InputOption::VALUE_REQUIRED, 'Admin password', 'corsair-password1')
            ->addOption('sample-data', null, InputOption::VALUE_NONE, 'Install Maho sample data')
            ->addOption('skip-reindex', null, InputOption::VALUE_NONE, 'Do not reindex and flush the cache after install');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (! is_file($this->composePath())) {
            $this->error('No Compose file found. Run `corsair:install --platform=maho` first.');

            return self::FAILURE;
        }

        if ($this->applicationServiceName() !== 'maho.test') {
            $this->error('This Compose project is not a Maho stack. Run `corsair:install --platform=maho` first.');

            return self::FAILURE;
        }

        if (! is_file($this->basePath('maho'))) {
            $this->error('Couldn\'t find ./maho. Create the application with `composer create-project mahocommerce/maho-starter` first.');

            return self::FAILURE;
        }

        $password = (string) $this->option('admin-password');

        if (strlen($password) < 7 || preg_match('/[a-z]/i', $password) !== 1 || preg_match('/\d/', $password) !== 1) {
            $this->error('Admin password must be at least 7 characters and include a letter and a number.');

            return self::FAILURE;
        }

        $command = $this->installationShellCommand();

        if (getenv('CORSAIR_SKIP_DOCKER') === '1') {
            $this->output->writeln($command);
        } elseif ($this->runCommands([$command]) !== 0) {
            $this->error('Maho installation failed.');

            return self::FAILURE;
        } else {
            $this->info('MahoCommerce installed.');
        }

        $url = $this->storeUrl($this->envVariables());
        $this->output->writeln('Storefront: ' . $url);
        $this->output->writeln('Admin: ' . $url . 'admin');
        $this->output->writeln('The app container runs `./maho cron:run default` and `./maho cron:run always` every minute.');

        if ($this->stackHasService('redis')) {
            $this->output->writeln('');
            $this->output->writeln('Redis is available, and the service uses maxmemory-policy volatile-lfu. Add this inside <global> in app/etc/local.xml:');
            $this->output->writeln(<<<'XML'

<cache>
    <backend>redis</backend>
    <backend_options>
        <dsn>redis://redis/0</dsn>
    </backend_options>
</cache>
<session_save>redis</session_save>
<redis_session>
    <dsn>redis://redis/1</dsn>
</redis_session>
XML);
        }

        return self::SUCCESS;
    }

    private function installationShellCommand(): string
    {
        $env = $this->envVariables();
        $url = $this->storeUrl($env);

        $arguments = [
            'php',
            './maho',
            'install',
            '--license_agreement_accepted=yes',
            '--locale=' . $this->option('locale'),
            '--timezone=' . $this->option('timezone'),
            '--default_currency=' . $this->option('currency'),
            '--db_host=' . $this->databaseHost($env),
            '--db_name=' . ($env['DB_DATABASE'] ?? 'maho'),
            '--db_user=' . ($env['DB_USERNAME'] ?? 'corsair'),
            '--db_pass=' . ($env['DB_PASSWORD'] ?? 'password'),
            '--url=' . $url,
            '--secure_base_url=' . $url,
            '--use_secure=0',
            '--use_secure_admin=0',
            '--admin_firstname=' . $this->option('admin-firstname'),
            '--admin_lastname=' . $this->option('admin-lastname'),
            '--admin_email=' . $this->option('admin-email'),
            '--admin_username=' . $this->option('admin-username'),
            '--admin_password=' . $this->option('admin-password'),
        ];

        if ($this->option('sample-data')) {
            $arguments[] = '--sample_data=1';
        }

        $commands = [$this->dockerComposeExec($arguments)];

        if (! $this->option('skip-reindex')) {
            $commands[] = $this->dockerComposeExec(['php', './maho', 'index:reindex:all']);
            $commands[] = $this->dockerComposeExec(['php', './maho', 'cache:flush']);
        }

        return implode(' && ', $commands);
    }

    /**
     * @param  array<string, string>  $env
     */
    private function databaseHost(array $env): string
    {
        $host = $env['DB_HOST'] ?? 'mysql';

        if (in_array($host, ['127.0.0.1', 'localhost', ''], true)) {
            $host = $this->stackHasService('mariadb') && ! $this->stackHasService('mysql') ? 'mariadb' : 'mysql';
        }

        $port = $env['DB_PORT'] ?? '3306';

        if ($port !== '' && $port !== '3306' && ! str_contains($host, ':')) {
            $host .= ':' . $port;
        }

        return $host;
    }

    /**
     * @param  array<string, string>  $env
     */
    private function storeUrl(array $env): string
    {
        $url = $this->option('url');

        if (! is_string($url) || $url === '') {
            $port = $env['APP_PORT'] ?? '80';
            $url = ($port === '' || $port === '80') ? 'http://localhost/' : 'http://localhost:' . $port . '/';
        }

        return str_ends_with($url, '/') ? $url : $url . '/';
    }
}
