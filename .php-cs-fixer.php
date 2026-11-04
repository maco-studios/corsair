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

use Ergebnis\License;
use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$license = License\Type\MIT::text(
    __DIR__ . '/LICENSE',
    License\Range::since(
        License\Year::fromString('2026'),
        new DateTimeZone('UTC'),
    ),
    License\Holder::fromString('Marcos "Marcão" Aurelio'),
    License\Url::fromString('https://github.com/maco-studios/corsair'),
);

$license->save();

$finder = Finder::create()
    ->ignoreDotFiles(false)
    ->in(__DIR__)
    ->exclude([
        'vendor',
        // Vendored from laravel/prompts (MIT) — see src/Prompts/LICENSE.md.
        // Not Corsair's own code, so it keeps its original style and license.
        'src/Prompts',
    ])
    ->notPath([
        '.php-cs-fixer.cache',
    ]);

return (new Config())
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS' => true,
        'header_comment' => [
            'comment_type' => 'PHPDoc',
            'header' => trim($license->header()),
            'location' => 'after_declare_strict',
            'separate' => 'both',
        ],
    ]);
