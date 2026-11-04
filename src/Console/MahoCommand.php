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

#[AsCommand(name: 'maho', description: 'Run the Maho CLI inside the application container')]
class MahoCommand extends Command
{
    use Concerns\InteractsWithDockerComposeServices;

    protected function configure(): void
    {
        $this
            ->setHelp('Arguments are passed to ./maho. Example: vendor/bin/corsair maho cache:flush')
            ->addArgument('args', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Arguments passed to ./maho')
            ->ignoreValidationErrors();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $arguments = $this->argument('args');

        return $this->forward(is_array($arguments) ? $arguments : []);
    }

    /**
     * @param  list<string>  $arguments
     */
    public function forward(array $arguments): int
    {
        $this->ensureIo();

        if (! is_file($this->basePath('maho'))) {
            $this->error('Couldn\'t find ./maho. Create the application with `composer create-project mahocommerce/maho-starter` first.');

            return self::FAILURE;
        }

        if ($this->applicationServiceName() !== 'maho.test') {
            $this->error('Couldn\'t find the maho.test service. Run `vendor/bin/corsair install --platform=maho` first.');

            return self::FAILURE;
        }

        $command = $this->dockerComposeExec(array_merge(['php', './maho'], $arguments));

        if (getenv('CORSAIR_SKIP_DOCKER') === '1') {
            $this->output->writeln($command);

            return self::SUCCESS;
        }

        return $this->runCommands([$command]) === 0 ? self::SUCCESS : self::FAILURE;
    }
}
