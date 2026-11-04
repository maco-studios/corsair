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

#[AsCommand(name: 'magento', description: 'Run the bin/magento CLI inside the application container')]
class MagentoCommand extends Command
{
    use Concerns\InteractsWithDockerComposeServices;

    protected function configure(): void
    {
        $this
            ->setHelp('Arguments are passed to bin/magento. Example: vendor/bin/corsair magento cache:flush')
            ->addArgument('args', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Arguments passed to bin/magento')
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

        if (! is_file($this->basePath('bin/magento'))) {
            $this->error('Couldn\'t find bin/magento. This command is only available for Magento 2 and Mage-OS.');

            return self::FAILURE;
        }

        if (! in_array($this->applicationServiceName(), ['magento.test', 'mageos.test'], true)) {
            $this->error('Couldn\'t find the magento.test or mageos.test service. Run `corsair:install` first.');

            return self::FAILURE;
        }

        $command = $this->dockerComposeExec(array_merge(['php', 'bin/magento'], $arguments));

        if (getenv('CORSAIR_SKIP_DOCKER') === '1') {
            $this->output->writeln($command);

            return self::SUCCESS;
        }

        return $this->runCommands([$command]) === 0 ? self::SUCCESS : self::FAILURE;
    }
}
