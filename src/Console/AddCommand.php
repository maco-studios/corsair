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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'add', description: 'Add a service to an existing Corsair installation')]
class AddCommand extends Command
{
    use Concerns\InteractsWithDockerComposeServices;

    protected function configure(): void
    {
        $this->addArgument(
            'services',
            InputArgument::OPTIONAL,
            'The services that should be added',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->argument('services')) {
            $services = $this->argument('services') === 'none'
                ? []
                : explode(',', (string) $this->argument('services'));
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

        $this->buildDockerCompose($services);
        $this->replaceEnvVariables($services);
        $this->prepareInstallation($services);

        $this->output->writeln('');
        $this->info('Additional Corsair services installed successfully.');

        return self::SUCCESS;
    }
}
