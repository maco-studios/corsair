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

#[AsCommand(name: 'corsair:install', description: 'Install Corsair\'s default Docker Compose file')]
class InstallCommand extends Command
{
    use Concerns\InteractsWithDockerComposeServices;

    protected function configure(): void
    {
        $this
            ->addOption('with', null, InputOption::VALUE_REQUIRED, 'The services that should be included in the installation')
            ->addOption('devcontainer', null, InputOption::VALUE_NONE, 'Create a .devcontainer configuration directory')
            ->addOption('php', null, InputOption::VALUE_REQUIRED, 'The PHP version that should be used', '8.3');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->option('with')) {
            $services = $this->option('with') === 'none' ? [] : explode(',', (string) $this->option('with'));
        } elseif (! $input->isInteractive()) {
            $services = $this->defaultServices;
        } else {
            $services = $this->gatherServicesInteractively();
        }

        $services = array_values(array_filter(array_map('trim', $services)));

        if ($invalidServices = array_diff($services, $this->services)) {
            $this->error('Invalid services [' . implode(',', $invalidServices) . '].');

            return self::FAILURE;
        }

        $this->buildDockerCompose($services);
        $this->replaceEnvVariables($services);

        if ($this->option('devcontainer')) {
            $this->installDevContainer();
        }

        $this->prepareInstallation($services);

        $this->output->writeln('');
        $this->info('Corsair scaffolding installed successfully. You may run your Docker containers using:');
        $this->output->writeln('<fg=gray>➜</> <options=bold>docker compose up -d</>');

        if (in_array('mysql', $services, true) || in_array('mariadb', $services, true)) {
            $this->warn('A database service was installed. Configure Magento to use the container hostname and credentials from your .env file.');
        }

        $this->output->writeln('');

        return self::SUCCESS;
    }

    protected function installDevContainer(): void
    {
        $directory = $this->basePath('.devcontainer');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents(
            $directory . DIRECTORY_SEPARATOR . 'devcontainer.json',
            (string) file_get_contents($this->stubPath('devcontainer.stub')),
        );

        $environment = (string) file_get_contents($this->basePath('.env'));
        $environment = $this->setEnvValue($environment, 'WWWGROUP', '1000');
        $environment = $this->setEnvValue($environment, 'WWWUSER', '1000');
        file_put_contents($this->basePath('.env'), $environment);
    }
}
