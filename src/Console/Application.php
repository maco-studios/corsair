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

final class Application extends SymfonyApplication
{
    public function getLongVersion(): string
    {
        return $this->signature() . \PHP_EOL . \PHP_EOL . parent::getLongVersion();
    }

    private function signature(): string
    {
        return <<<'SIGNATURE'
  █████╗ ██████╗ ██████╗ ███████╗ █████╗ ██╗██████╗
 ██╔══██╗██╔═══╝██╔══██╗██╔════╝██╔══██╗██║██╔══██╗
 ██║  ╚═╝██████╗██████╔╝███████╗███████║██║██████╔╝
 ██║  ██╗██╔═══╝██╔══██╗╚════██║██╔══██║██║██╔══██╗
 ╚█████╔╝██████╗██║  ██║███████║██║  ██║██║██║  ██║
  ╚════╝ ╚═════╝╚═╝  ╚═╝╚══════╝╚═╝  ╚═╝╚═╝╚═╝  ╚═╝
SIGNATURE;
    }
}
