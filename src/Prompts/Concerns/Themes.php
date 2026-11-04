<?php

namespace Corsair\Prompts\Concerns;

use Corsair\Prompts\MultiSelectPrompt;
use Corsair\Prompts\Prompt;
use Corsair\Prompts\SelectPrompt;
use Corsair\Prompts\Themes\Default\MultiSelectPromptRenderer;
use Corsair\Prompts\Themes\Default\SelectPromptRenderer;
use InvalidArgumentException;

trait Themes
{
    /**
     * The name of the active theme.
     */
    protected static string $theme = 'default';

    /**
     * The available themes.
     *
     * Trimmed down from Laravel Prompts' original theme map to the two
     * prompt types Corsair actually uses (select, multiselect).
     *
     * @var array<string, array<class-string<Prompt>, class-string<object&callable>>>
     */
    protected static array $themes = [
        'default' => [
            SelectPrompt::class => SelectPromptRenderer::class,
            MultiSelectPrompt::class => MultiSelectPromptRenderer::class,
        ],
    ];

    /**
     * Get or set the active theme.
     *
     * @throws InvalidArgumentException
     */
    public static function theme(?string $name = null): string
    {
        if ($name === null) {
            return static::$theme;
        }

        if (! isset(static::$themes[$name])) {
            throw new InvalidArgumentException("Prompt theme [{$name}] not found.");
        }

        return static::$theme = $name;
    }

    /**
     * Add a new theme.
     *
     * @param  array<class-string<Prompt>, class-string<object&callable>>  $renderers
     */
    public static function addTheme(string $name, array $renderers): void
    {
        if ($name === 'default') {
            throw new InvalidArgumentException('The default theme cannot be overridden.');
        }

        static::$themes[$name] = $renderers;
    }

    /**
     * Get the renderer for the current prompt.
     */
    protected function getRenderer(): callable
    {
        $class = get_class($this);

        return new (static::$themes[static::$theme][$class] ?? static::$themes['default'][$class])($this);
    }

    /**
     * Render the prompt using the active theme.
     */
    protected function renderTheme(): string
    {
        $renderer = $this->getRenderer();

        return $renderer($this);
    }
}
