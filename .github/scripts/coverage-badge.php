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

// Renders a static flat-style SVG badge ("coverage | NN%") from a Clover
// coverage report, with no external badge service involved.
// Usage: php coverage-badge.php <clover.xml> <output.svg>

[, $cloverPath, $outputPath] = $argv;

$clover = simplexml_load_file($cloverPath);
if ($clover === false) {
    fwrite(STDERR, "Could not parse clover report at {$cloverPath}\n");
    exit(1);
}

$metrics = $clover->project->metrics;
$statements = (int) $metrics['statements'];
$covered = (int) $metrics['coveredstatements'];

$percent = $statements > 0 ? round(($covered / $statements) * 100, 1) : 0.0;
$text = rtrim(rtrim(sprintf('%.1f', $percent), '0'), '.') . '%';

$color = match (true) {
    $percent >= 90 => '#4c1',
    $percent >= 75 => '#97ca00',
    $percent >= 60 => '#dfb317',
    $percent >= 40 => '#fe7d37',
    default => '#e05d44',
};

function textWidth(string $text): int
{
    return (int) round(strlen($text) * 6.5) + 10;
}

$label = 'coverage';
$labelWidth = textWidth($label);
$valueWidth = textWidth($text);
$totalWidth = $labelWidth + $valueWidth;
$labelCenter = (int) round($labelWidth / 2);
$valueCenter = (int) round($labelWidth + $valueWidth / 2);

$svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$totalWidth}" height="20" role="img" aria-label="{$label}: {$text}">
  <linearGradient id="s" x2="0" y2="100%">
    <stop offset="0" stop-color="#bbb" stop-opacity=".1"/>
    <stop offset="1" stop-opacity=".1"/>
  </linearGradient>
  <mask id="m">
    <rect width="{$totalWidth}" height="20" rx="3" fill="#fff"/>
  </mask>
  <g mask="url(#m)">
    <rect width="{$labelWidth}" height="20" fill="#555"/>
    <rect x="{$labelWidth}" width="{$valueWidth}" height="20" fill="{$color}"/>
    <rect width="{$totalWidth}" height="20" fill="url(#s)"/>
  </g>
  <g fill="#fff" text-anchor="middle" font-family="Verdana,Geneva,DejaVu Sans,sans-serif" font-size="11">
    <text x="{$labelCenter}" y="14">{$label}</text>
    <text x="{$valueCenter}" y="14">{$text}</text>
  </g>
</svg>
SVG;

file_put_contents($outputPath, $svg);

fwrite(STDOUT, "Coverage: {$text} -> {$outputPath}\n");
