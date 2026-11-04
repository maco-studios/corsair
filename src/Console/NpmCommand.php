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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Forwards arguments to `npm` inside the application container, where
 * Node.js and npm are already installed by the runtime image.
 */
class NpmCommand extends Command
{
    use Concerns\InteractsWithDockerComposeServices;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $arguments
     */
    public function forward(array $arguments): int
    {
        $this->ensureIo();

        if (! is_file($this->composePath())) {
            $this->error('No Compose file found. Run `corsair:install` first.');

            return self::FAILURE;
        }

        $command = $this->dockerComposeExec(array_merge(['npm'], $arguments));

        if (getenv('CORSAIR_SKIP_DOCKER') === '1') {
            $this->output->writeln($command);

            return self::SUCCESS;
        }

        return $this->runCommands([$command]) === 0 ? self::SUCCESS : self::FAILURE;
    }
}
