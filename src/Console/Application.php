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

use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\ListCommand as SymfonyListCommand;

final class Application extends SymfonyApplication
{
    public function getLongVersion(): string
    {
        return $this->signature() . \PHP_EOL . \PHP_EOL . parent::getLongVersion();
    }

    /**
     * Swaps in Corsair's own ListCommand so `list`/the no-argument default
     * groups Corsair's commands separately from Symfony's own
     * help/list/completion — see ListCommand's docblock for why that needs
     * a replacement command rather than just a config tweak.
     *
     * @return list<\Symfony\Component\Console\Command\Command>
     */
    protected function getDefaultCommands(): array
    {
        return array_map(
            static fn(\Symfony\Component\Console\Command\Command $command): \Symfony\Component\Console\Command\Command => $command instanceof SymfonyListCommand ? new ListCommand() : $command,
            parent::getDefaultCommands(),
        );
    }

    private function signature(): string
    {
        return Banner::render();
    }
}
