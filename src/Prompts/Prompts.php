<?php

/**
 * Trimmed from Laravel Prompts (laravel/prompts, MIT licensed) down to the
 * select() and multiselect() prompts Corsair's TUI actually uses, and
 * vendored under Corsair\Prompts so Corsair doesn't carry a hard dependency
 * on laravel/prompts' symfony/console ^6.2+ floor — which makes it
 * uninstallable alongside platforms (OpenMage) whose legacy installer
 * plugins cap symfony/console at ^5.0. See LICENSE.md in this directory.
 *
 * Exposed as static methods on a PSR-4 class rather than Laravel Prompts'
 * original global functions (loaded via a Composer "files" autoload entry):
 * Corsair gets `composer require`d into target projects as a symlinked
 * `path` repository, and that symlink only resolves on the host — inside
 * the app's own Docker container (which bind-mounts just the target
 * project directory), it's dangling. Composer "files" entries are required
 * unconditionally on every bootstrap, including the containerized app's
 * own, which never calls into Corsair at all; a PSR-4 class is loaded
 * lazily, only when Corsair's own (host-side) commands actually call it.
 *
 * @see https://github.com/laravel/prompts
 */

namespace Corsair\Prompts;

use Closure;

class Prompts
{
    /**
     * Prompt the user to select an option.
     *
     * @param  array<int|string, string>  $options
     * @param  true|string  $required
     */
    public static function select(
        string $label,
        array $options,
        int|string|null $default = null,
        int $scroll = 5,
        mixed $validate = null,
        string $hint = '',
        bool|string $required = true,
        ?Closure $transform = null,
        string|Closure $info = '',
    ): int|string {
        return (new SelectPrompt(...get_defined_vars()))->prompt();
    }

    /**
     * Prompt the user to select multiple options.
     *
     * @param  array<int|string, string>  $options
     * @param  array<int|string>  $default
     * @return array<int|string>
     */
    public static function multiselect(
        string $label,
        array $options,
        array $default = [],
        int $scroll = 5,
        bool|string $required = false,
        mixed $validate = null,
        string $hint = 'Use the space bar to select options.',
        ?Closure $transform = null,
        string|Closure $info = '',
    ): array {
        return (new MultiSelectPrompt(...get_defined_vars()))->prompt();
    }
}
