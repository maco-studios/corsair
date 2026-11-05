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
use Corsair\Prompts\Output\BufferedConsoleOutput;
use Corsair\Prompts\Output\ConsoleOutput;
use Corsair\Prompts\Prompt;
use Corsair\Prompts\SelectPrompt;
use Corsair\Prompts\Terminal;
use Corsair\Prompts\Themes\Default\Concerns\DrawsScrollbars;
use Corsair\Prompts\Themes\Default\Concerns\InteractsWithStrings;
use Corsair\Prompts\Themes\Default\SelectPromptRenderer;
use Corsair\Prompts\Support\Result;

class CoverageRenderer extends SelectPromptRenderer
{
    use DrawsScrollbars;

    public function longestFor(array $lines, int $padding = 0): int
    {
        return $this->longest($lines, $padding);
    }

    public function padFor(string $text, int $length, string $char = ' '): string
    {
        return $this->pad($text, $length, $char);
    }

    public function stripFor(string $text): string
    {
        return $this->stripEscapeSequences($text);
    }

    public function wrapFor(string $text, int $width, bool $cut = false): string
    {
        return $this->mbWordwrap($text, $width, "\n", $cut);
    }

    public function ansiWrapFor(string $text, int $width): array
    {
        return $this->ansiWordwrap($text, $width);
    }

    public function truncateFor(string $text, int $width): string
    {
        return $this->truncate($text, $width);
    }

    public function scrollbarFor(array $visible, int $first, int $height, int $total, int $width): array
    {
        return $this->scrollbar($visible, $first, $height, $total, $width);
    }

    public function scrollPositionFor(int $first, int $height, int $total): int
    {
        return $this->scrollPosition($first, $height, $total);
    }
}

afterEach(function (): void {
    SelectPrompt::flushState();
    SelectPrompt::theme('default');
});

it('covers prompt utility traits and ANSI-aware rendering helpers', function (): void {
    SelectPrompt::fake();
    $prompt = new SelectPrompt('Render', ['one', 'two']);
    $renderer = new CoverageRenderer($prompt);
    $colored = "\e[31mred\e[39m <info>green</info> <fg=blue>blue</>";
    $link = "\e]8;;https://example.test\e\\linked\e]8;;\e\\";

    expect($renderer->longestFor([]))->toBe(60)
        ->and($renderer->longestFor(['one', 'long'], 2))->toBe(60)
        ->and($renderer->padFor("\e[31mone\e[39m", 5, '.'))->toBe("\e[31mone\e[39m..")
        ->and($renderer->stripFor($colored))->toBe('red green blue')
        ->and($renderer->truncateFor('abcdef', 4))->toBe('abc…')
        ->and($renderer->wrapFor('short text', 30))->toBe('short text')
        ->and($renderer->wrapFor('abcdefgh', 3, true))->toContain("abc\ndef")
        ->and($renderer->wrapFor('one two three', 7))->toContain("one two\nthree")
        ->and($renderer->ansiWrapFor($colored . ' ' . $link, 8))->not->toBe([])
        ->and($renderer->scrollbarFor(['one', 'two'], 0, 2, 4, 5))->toHaveCount(2)
        ->and($renderer->scrollbarFor(['one', 'two'], 0, 2, 2, 5))->toBe(['one', 'two'])
        ->and($renderer->scrollPositionFor(0, 4, 10))->toBe(0)
        ->and($renderer->scrollPositionFor(6, 4, 10))->toBe(3)
        ->and($renderer->scrollPositionFor(2, 2, 10))->toBe(-1)
        ->and($renderer->scrollPositionFor(2, 4, 10))->toBe(1);

    expect(fn(): string => $renderer->truncateFor('text', 0))->toThrow(InvalidArgumentException::class);
});

it('covers colors, cursor, erase, events, output buffers, and key loops', function (): void {
    SelectPrompt::fake([Key::ENTER]);
    $prompt = new SelectPrompt('Utilities', ['one']);

    foreach (['reset', 'bold', 'dim', 'italic', 'underline', 'inverse', 'hidden', 'strikethrough', 'black', 'red', 'green', 'yellow', 'blue', 'magenta', 'cyan', 'white', 'bgBlack', 'bgRed', 'bgGreen', 'bgYellow', 'bgBlue', 'bgMagenta', 'bgCyan', 'bgWhite', 'gray'] as $method) {
        expect($prompt->{$method}('text'))->toContain('text');
    }

    $prompt->hideCursor();
    $prompt->moveCursor(-1, 1);
    $prompt->moveCursor(1, -1);
    $prompt->moveCursorToColumn(2);
    $prompt->moveCursorUp(1);
    $prompt->eraseLines(2);
    $prompt->eraseLines(0);
    $prompt->eraseDown();
    $prompt->showCursor();

    $events = [];
    $prompt->on('test', function (string $value) use (&$events): void {
        $events[] = $value;
    });
    $prompt->emit('test', 'value');
    $prompt->clearListeners();
    $prompt->emit('test', 'ignored');

    expect($events)->toBe(['value'])
        ->and($prompt->runLoop(fn(string $key): Result => Result::from($key)))->toBe(Key::ENTER);

    $output = new BufferedConsoleOutput();
    $output->write('one');
    $output->writeln('two');
    $output->writeDirectly('three');

    expect($output->content())->toContain('onetwo')
        ->and($output->fetch())->toContain('three')
        ->and($output->content())->toBe('');

    $console = new ConsoleOutput();
    $console->writeln('line');
    $console->writeDirectly('direct');
    expect($console->newLinesWritten())->toBe(1);
});

it('covers terminal dimensions, colors, and theme extension', function (): void {
    putenv('COLORTERM=');
    $terminal = new Terminal();

    expect($terminal->cols())->toBeGreaterThan(0)
        ->and($terminal->lines())->toBeGreaterThan(0)
        ->and($terminal->supportsTrueColor())->toBeFalse()
        ->and(withWarningsSuppressed(fn(): array => $terminal->foregroundColor()))->toHaveCount(3)
        ->and(withWarningsSuppressed(fn(): array => $terminal->backgroundColor()))->toHaveCount(3);

    SelectPrompt::addTheme('coverage', [SelectPrompt::class => SelectPromptRenderer::class]);
    expect(SelectPrompt::theme('coverage'))->toBe('coverage');
});
