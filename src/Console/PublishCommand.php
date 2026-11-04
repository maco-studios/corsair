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

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'publish', description: 'Publish the Corsair Docker files')]
class PublishCommand extends Command
{
    use Concerns\InteractsWithDockerComposeServices;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $destination = $this->basePath('docker');

        $this->copyDirectory($this->packagePath('resources/runtimes'), $destination);
        $this->copyDirectory($this->packagePath('resources/database'), $destination);

        $composePath = $this->composePath();

        if (! file_exists($composePath)) {
            $this->error('No Compose file found. Run `vendor/bin/corsair install` first.');

            return self::FAILURE;
        }

        $contents = (string) file_get_contents($composePath);

        $contents = str_replace(
            [
                './vendor/maco-studios/corsair/resources/runtimes/8.5',
                './vendor/maco-studios/corsair/resources/runtimes/8.4',
                './vendor/maco-studios/corsair/resources/runtimes/8.3',
                './vendor/maco-studios/corsair/resources/runtimes/8.2',
                './vendor/maco-studios/corsair/resources/runtimes/8.1',
                './vendor/maco-studios/corsair/resources/runtimes/8.0',
                './vendor/maco-studios/corsair/resources/database/mariadb',
                './vendor/maco-studios/corsair/resources/database/mysql',
                './vendor/maco-studios/corsair/resources/database/pgsql',
            ],
            [
                './docker/8.5',
                './docker/8.4',
                './docker/8.3',
                './docker/8.2',
                './docker/8.1',
                './docker/8.0',
                './docker/mariadb',
                './docker/mysql',
                './docker/pgsql',
            ],
            $contents,
        );

        file_put_contents($composePath, $contents);

        $this->info('Corsair Docker and database files published to ./docker and Compose paths updated.');

        return self::SUCCESS;
    }

    private function copyDirectory(string $source, string $destination): void
    {
        if (! is_dir($source)) {
            return;
        }

        if (! is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            $target = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathname();

            if ($file->isDir()) {
                if (! is_dir($target)) {
                    mkdir($target, 0755, true);
                }

                continue;
            }

            $directory = dirname($target);

            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            copy($file->getPathname(), $target);
        }
    }
}
