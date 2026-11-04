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

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class Command extends SymfonyCommand
{
    protected InputInterface $input;

    protected OutputInterface $output;

    protected SymfonyStyle $io;

    private ?string $projectPath = null;

    public function setProjectPath(string $path): static
    {
        $this->projectPath = $path;

        return $this;
    }

    protected function basePath(string $path = ''): string
    {
        $root = $this->projectPath ?? getcwd() ?: '.';

        return $path === ''
            ? $root
            : $root . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->input = $input;
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function option(string $name): mixed
    {
        return $this->input->getOption($name);
    }

    protected function argument(string $name): mixed
    {
        return $this->input->getArgument($name);
    }

    protected function hasOption(string $name): bool
    {
        return $this->getDefinition()->hasOption($name);
    }

    protected function info(string $message): void
    {
        $this->io->success($message);
    }

    protected function warn(string $message): void
    {
        $this->io->warning($message);
    }

    protected function error(string $message): void
    {
        $this->io->error($message);
    }

    /**
     * Sets up input/output/io when the command is used directly (via
     * `forward()`) instead of through the Symfony Console application.
     */
    protected function ensureIo(): void
    {
        if (isset($this->io)) {
            return;
        }

        $this->input = new ArrayInput([]);
        $this->output = new ConsoleOutput();
        $this->io = new SymfonyStyle($this->input, $this->output);
    }
}
