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
 * Forwards any command line argument that isn't one of Corsair's own
 * commands straight to `docker compose`, the same way `vendor/bin/sail`
 * forwards to `docker-compose` for Laravel. This is what lets
 * `vendor/bin/corsair up -d` work as an alias for
 * `docker compose -f compose.yaml up -d`.
 */
class DockerComposeCommand extends Command
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

        $command = sprintf(
            'docker compose -f %s %s',
            escapeshellarg(basename($this->composePath())),
            implode(' ', array_map('escapeshellarg', $arguments)),
        );

        if (getenv('CORSAIR_SKIP_DOCKER') === '1') {
            $this->output->writeln($command);

            return self::SUCCESS;
        }

        return $this->runCommands([$command]) === 0 ? self::SUCCESS : self::FAILURE;
    }
}
