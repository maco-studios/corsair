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

use Corsair\Prompts\Key;
use Corsair\Prompts\MultiSelectPrompt;
use Corsair\Prompts\Prompts;
use Corsair\Prompts\SelectPrompt;
use Corsair\Prompts\Support\Result;
use Corsair\Prompts\Themes\Default\MultiSelectPromptRenderer;
use Corsair\Prompts\Themes\Default\SelectPromptRenderer;
use RuntimeException;

afterEach(function (): void {
    SelectPrompt::flushState();
    MultiSelectPrompt::flushState();
});

it('selects an option interactively and renders the active and submitted states', function (): void {
    SelectPrompt::fake([Key::DOWN, Key::ENTER]);

    $prompt = new SelectPrompt('Choose a runtime', ['8.3', '8.4'], info: 'Pick one', hint: 'Use arrows');
    $result = $prompt->prompt();

    expect($result)->toBe('8.4')
        ->and($prompt->value())->toBe('8.4')
        ->and($prompt->label())->toBe('8.4')
        ->and($prompt->visible())->toBe(['8.3', '8.4'])
        ->and($prompt->newLinesWritten())->toBeGreaterThanOrEqual(1);

    SelectPrompt::assertStrippedOutputContains('Choose a runtime');
    SelectPrompt::assertStrippedOutputContains('8.4');
});

it('supports associative select options, defaults, info callbacks, and validation recovery', function (): void {
    SelectPrompt::fake([Key::ENTER, Key::DOWN, Key::ENTER]);

    $prompt = new SelectPrompt(
        'Choose a service',
        ['db' => 'Database', 'app' => 'Application'],
        default: 'db',
        validate: fn(string $value): ?string => $value === 'db' ? 'Choose another service.' : null,
        info: fn(string $value): string => "Selected: {$value}",
    );

    expect($prompt->infoText())->toBe('Selected: db')
        ->and($prompt->prompt())->toBe('app')
        ->and($prompt->highlightedValue())->toBe('app')
        ->and($prompt->label())->toBe('Application');

    SelectPrompt::assertStrippedOutputContains('Choose another service.');
});

it('supports select cancellation, transforms, and non-interactive defaults', function (): void {
    SelectPrompt::fake([Key::CTRL_C]);
    $prompt = new SelectPrompt('Choose', ['first'], transform: fn(string $value): string => strtoupper($value));

    expect($prompt->prompt())->toBe('FIRST');
    SelectPrompt::assertStrippedOutputContains('Cancelled.');

    SelectPrompt::interactive(false);
    $default = new SelectPrompt('Default', ['first'], default: 'first', transform: fn(string $value): string => strtoupper($value));

    expect($default->prompt())->toBe('first');
});

it('supports custom fallback implementations and rejects missing fallbacks', function (): void {
    SelectPrompt::fallbackWhen(true);
    $prompt = new SelectPrompt('Fallback', ['first']);

    expect(fn() => $prompt->fallback())->toThrow(RuntimeException::class);

    SelectPrompt::fallbackUsing(fn(SelectPrompt $prompt): string => $prompt->label . ' fallback');

    expect($prompt->prompt())->toBe('Fallback fallback');
});

it('supports custom validation and cancellation callbacks', function (): void {
    SelectPrompt::validateUsing(fn(SelectPrompt $prompt): ?string => $prompt->value() === 'first' ? 'Use the second option.' : null);
    SelectPrompt::cancelUsing(fn(): string => 'cancelled by callback');
    SelectPrompt::fake([Key::ENTER, Key::CTRL_C]);

    $prompt = new SelectPrompt('Callbacks', ['first', 'second']);

    expect($prompt->prompt())->toBe('cancelled by callback');

    SelectPrompt::preventReverting();
    SelectPrompt::fake([Key::CTRL_U, Key::CTRL_C]);
    $revertable = new SelectPrompt('Revert', ['first']);

    expect($revertable->prompt())->toBe('cancelled by callback');
    SelectPrompt::assertStrippedOutputContains('This cannot be reverted.');
});

it('selects and toggles multiple options in list and associative collections', function (): void {
    MultiSelectPrompt::fake([Key::SPACE, Key::DOWN, Key::SPACE, Key::ENTER]);
    $prompt = new MultiSelectPrompt('Choose services', ['database', 'cache'], hint: 'Toggle services');

    expect($prompt->prompt())->toBe(['database', 'cache'])
        ->and($prompt->labels())->toBe(['database', 'cache'])
        ->and($prompt->isSelected('database'))->toBeTrue()
        ->and($prompt->isHighlighted('cache'))->toBeTrue();

    MultiSelectPrompt::fake([Key::CTRL_A, Key::ENTER]);
    $associative = new MultiSelectPrompt('Choose named services', ['db' => 'Database', 'redis' => 'Redis']);

    expect($associative->prompt())->toBe(['db', 'redis'])
        ->and($associative->labels())->toBe(['Database', 'Redis'])
        ->and($associative->isSelected('db'))->toBeTrue();
});

it('renders multiselect errors, cancellation, selected summaries, and scrolling info', function (): void {
    MultiSelectPrompt::fake([Key::ENTER, Key::CTRL_C]);
    $prompt = new MultiSelectPrompt(
        'Choose services',
        ['db', 'cache', 'queue', 'search', 'mail', 'files'],
        scroll: 2,
        required: 'At least one service is required.',
        info: 'Required services',
    );

    expect($prompt->prompt())->toBe([]);
    MultiSelectPrompt::assertStrippedOutputContains('At least one service is required.');
    MultiSelectPrompt::assertStrippedOutputContains('Cancelled.');

    MultiSelectPrompt::fake([Key::SPACE, Key::ENTER]);
    $selected = new MultiSelectPrompt('Summary', ['db', 'cache', 'queue'], scroll: 2);

    expect($selected->prompt())->toBe(['db'])
        ->and($selected->visible())->toHaveCount(2);

    expect(new MultiSelectPromptRenderer($selected))->toBeInstanceOf(MultiSelectPromptRenderer::class);
});

it('exposes prompt themes, renderers, key matching, and result sentinels', function (): void {
    expect(Key::oneOf([Key::HOME, Key::END], Key::END[0]))
        ->toBe(Key::END[0])
        ->and(Key::oneOf([Key::HOME], 'x'))->toBeNull()
        ->and(Result::from('value')->value)->toBe('value');

    expect(SelectPrompt::theme())->toBe('default')
        ->and(SelectPrompt::theme('default'))->toBe('default')
        ->and((new SelectPromptRenderer(new SelectPrompt('Render', ['one']))))->toBeInstanceOf(SelectPromptRenderer::class);

    expect(fn() => SelectPrompt::theme('missing'))->toThrow(InvalidArgumentException::class)
        ->and(fn() => SelectPrompt::addTheme('default', []))->toThrow(InvalidArgumentException::class);
});
