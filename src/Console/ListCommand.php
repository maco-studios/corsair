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

use Symfony\Component\Console\Command\ListCommand as SymfonyListCommand;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Corsair's own commands (`install`, `add`, `maho`, ...) have no `:`
 * namespace — that prefix was dropped because typing `corsair corsair:install`
 * felt redundant. Symfony's own command grouping in `list` is driven purely
 * by that namespace colon, though, so without one, Corsair's commands would
 * sit in the exact same "Available commands" section as Symfony's own
 * `help`/`list`/`completion`. This overrides the plain-text, unfiltered
 * default view to split them into their own section instead, while leaving
 * `--raw`, `--format`, and the `namespace` argument to Symfony's own
 * rendering untouched (those are for scripting/completion, not for a human
 * reading the default output).
 */
final class ListCommand extends SymfonyListCommand
{
    /**
     * Symfony's own default commands, as named in Application::getDefaultCommands().
     *
     * @var list<string>
     */
    private const BUILT_IN_COMMANDS = ['help', 'list', 'completion', '_complete'];

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $isDefaultView = $input->getOption('format') === 'txt'
            && ! $input->getOption('raw')
            && ! $input->getOption('short')
            && $input->getArgument('namespace') === null;

        $application = $this->getApplication();

        if (! $isDefaultView || $application === null) {
            return parent::execute($input, $output);
        }

        if ('' !== $help = $application->getHelp()) {
            $output->writeln($help);
            $output->writeln('');
        }

        $output->writeln('<comment>Usage:</comment>');
        $output->writeln('  command [options] [arguments]');
        $output->writeln('');

        // Describe options only, not the full application definition — that
        // also carries the "command" argument Symfony uses internally to
        // route to a command, which isn't meant to show up here.
        (new DescriptorHelper())->describe($output, new InputDefinition($application->getDefinition()->getOptions()));

        $output->writeln('');
        $output->writeln('');

        $commands = array_filter($application->all(), static fn($command) => ! $command->isHidden());
        $builtIn = array_intersect_key($commands, array_flip(self::BUILT_IN_COMMANDS));
        $corsair = array_diff_key($commands, $builtIn);

        $width = max(array_map(
            static fn(string $name): int => Helper::width($name),
            array_keys($commands),
        ));

        $output->writeln('<comment>Available commands:</comment>');
        $this->writeCommands($output, $builtIn, $width);

        if ($corsair !== []) {
            $output->writeln('');
            $output->writeln(' <comment>corsair</comment>');
            $this->writeCommands($output, $corsair, $width);
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, \Symfony\Component\Console\Command\Command>  $commands
     */
    private function writeCommands(OutputInterface $output, array $commands, int $width): void
    {
        ksort($commands);

        foreach ($commands as $name => $command) {
            $spacing = str_repeat(' ', $width - Helper::width($name));
            $output->writeln(sprintf('  <info>%s</info>%s %s', $name, $spacing, $command->getDescription()));
        }
    }
}
