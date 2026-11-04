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

#[AsCommand(name: 'install', description: 'Install Corsair\'s default Docker Compose file')]
class InstallCommand extends Command
{
    use Concerns\InteractsWithDockerComposeServices;

    protected function configure(): void
    {
        $this
            ->addOption('with', null, InputOption::VALUE_REQUIRED, 'The services that should be included in the installation')
            ->addOption('devcontainer', null, InputOption::VALUE_NONE, 'Create a .devcontainer configuration directory')
            ->addOption('platform', null, InputOption::VALUE_REQUIRED, 'The application platform (magento or maho)')
            ->addOption('php', null, InputOption::VALUE_REQUIRED, 'The PHP version that should be used');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->option('with')) {
            $services = $this->option('with') === 'none' ? [] : explode(',', (string) $this->option('with'));
        } elseif (! $input->isInteractive()) {
            $services = $this->defaultServices();
        } else {
            $services = $this->gatherServicesInteractively();
        }

        $services = array_values(array_filter(array_map(trim(...), $services)));

        if ($invalidServices = array_diff($services, $this->services)) {
            $this->error('Invalid services [' . implode(',', $invalidServices) . '].');

            return self::FAILURE;
        }

        if (! in_array($this->platform(), $this->platforms, true)) {
            $this->error('Invalid platform [' . $this->platform() . ']. Supported platforms: ' . implode(', ', $this->platforms) . '.');

            return self::FAILURE;
        }

        if (! in_array($this->phpVersion(), $this->phpVersions, true)) {
            $this->error('Invalid PHP version [' . $this->phpVersion() . ']. Supported versions: ' . implode(', ', $this->phpVersions) . '.');

            return self::FAILURE;
        }

        if ($this->platform() === 'maho' && version_compare($this->phpVersion(), '8.3', '<')) {
            $this->error('Maho requires PHP 8.3 or later. Maho 26.11 requires PHP 8.5 or later.');

            return self::FAILURE;
        }

        if ($this->platform() === 'openmage' && version_compare($this->phpVersion(), '8.1', '<')) {
            $this->error('OpenMage requires PHP 8.1 or later.');

            return self::FAILURE;
        }

        $this->buildDockerCompose($services);
        $this->replaceEnvVariables($services);

        if ($this->option('devcontainer')) {
            $this->installDevContainer();
        }

        $this->prepareInstallation($services);

        if ($this->platform() === 'maho' && version_compare($this->phpVersion(), '8.5', '<')) {
            $this->warn('Maho 26.11 requires PHP 8.5 or later. PHP ' . $this->phpVersion() . ' only runs Maho 26.9.');
        }

        $this->output->writeln('');
        $this->info('Corsair scaffolding installed successfully. You may run your Docker containers using:');
        $this->output->writeln('<fg=gray>➜</> <options=bold>vendor/bin/corsair up -d</>');

        if ($this->platform() === 'maho') {
            $this->output->writeln('Maho serves the store from <options=bold>public/</>. Install the application once the containers are up, the same way you would for Magento or OpenMage — run its own installer yourself:');
            $this->output->writeln('<fg=gray>➜</> <options=bold>vendor/bin/corsair maho install --license_agreement_accepted=yes --db_host=mysql --db_name=maho --db_user=corsair --db_pass=password --url=http://localhost/ --admin_username=admin --admin_password=<password></>');
            $this->output->writeln('Run any other Maho CLI command with <options=bold>vendor/bin/corsair maho</>.');
        }

        if (in_array('mysql', $services, true) || in_array('mariadb', $services, true)) {
            $this->warn('A database service was installed. Use the container hostname and credentials from your .env file when running the application\'s own installer.');
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

        $devcontainer = (string) file_get_contents($this->stubPath('devcontainer.stub'));

        if ($this->platform() === 'maho') {
            $devcontainer = str_replace(
                ['Corsair Magento', 'magento.test'],
                ['Corsair Maho', 'maho.test'],
                $devcontainer,
            );
        }

        file_put_contents($directory . DIRECTORY_SEPARATOR . 'devcontainer.json', $devcontainer);

        $environment = (string) file_get_contents($this->basePath('.env'));
        $environment = $this->setEnvValue($environment, 'WWWGROUP', '1000');
        $environment = $this->setEnvValue($environment, 'WWWUSER', '1000');
        file_put_contents($this->basePath('.env'), $environment);
    }
}
