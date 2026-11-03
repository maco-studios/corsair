<?php

/**
 * Copyright (c) 2026 Marcos "Marcão" Aurelio
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/maco-studios/corsair
 */

use Symfony\Component\Yaml\Yaml;
use Tests\Fixtures\TestComposeCommand;

beforeEach(function (): void {
    $this->project = temporaryProjectPath('corsair-unit');
    mkdir($this->project, 0755, true);
});

afterEach(function (): void {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->project, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iterator as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }

    rmdir($this->project);
});

function composeCommand(string $project): TestComposeCommand
{
    return (new TestComposeCommand())->setProjectPath($project);
}

it('prefers the explicit --platform option over anything else', function (): void {
    file_put_contents($this->project . '/composer.json', json_encode(['require' => ['mahocommerce/maho' => '*']]));

    $command = composeCommand($this->project)->withInput(['--platform' => 'OpenMage']);

    expect($command->callPlatform())->toBe('openmage');
});

it('detects the platform from an existing CORSAIR_PLATFORM environment value', function (): void {
    file_put_contents($this->project . '/compose.yaml', Yaml::dump([
        'services' => [
            'mageos.test' => ['environment' => ['CORSAIR_PLATFORM' => 'mageos']],
        ],
    ]));

    $command = composeCommand($this->project)->withInput();

    expect($command->callPlatform())->toBe('mageos');
});

it('falls back to the maho.test service key when no CORSAIR_PLATFORM value is set', function (): void {
    file_put_contents($this->project . '/compose.yaml', Yaml::dump([
        'services' => [
            'maho.test' => ['image' => 'corsair-8.5/app'],
        ],
    ]));

    $command = composeCommand($this->project)->withInput();

    expect($command->callPlatform())->toBe('maho');
});

it('caches the resolved platform after the first call', function (): void {
    file_put_contents($this->project . '/composer.json', json_encode(['require' => ['openmage/magento-lts' => '*']]));

    $command = composeCommand($this->project)->withInput();

    expect($command->callPlatform())->toBe('openmage');

    // Once resolved and cached, a composer.json written afterwards must not change it.
    file_put_contents($this->project . '/composer.json', json_encode(['require' => ['mage-os/mageos-magento2' => '*']]));

    expect($command->callPlatform())->toBe('openmage');
});

it('falls back to magento non-interactively when nothing identifies the platform', function (): void {
    $command = composeCommand($this->project)->withInput();

    expect($command->callPlatform())->toBe('magento');
});

dataset('composer platforms', [
    'maho' => ['mahocommerce/maho-starter', 'maho'],
    'openmage' => ['openmage/magento-lts', 'openmage'],
    'mage-os' => ['mage-os/mageos-magento2', 'mageos'],
    'magento' => ['magento/product-community-edition', 'magento'],
]);

it('detects the platform from composer.json require packages', function (string $package, string $expected): void {
    file_put_contents($this->project . '/composer.json', json_encode(['require' => [$package => '*']]));

    $command = composeCommand($this->project)->withInput();

    expect($command->callPlatformFromComposerJson())->toBe($expected)
        ->and($command->callPlatform())->toBe($expected);
})->with('composer platforms');

it('also looks at require-dev packages in composer.json', function (): void {
    file_put_contents($this->project . '/composer.json', json_encode(['require-dev' => ['mage-os/mageos-magento2' => '*']]));

    $command = composeCommand($this->project)->withInput();

    expect($command->callPlatformFromComposerJson())->toBe('mageos');
});

it('ignores a composer.json that is not a valid JSON object', function (): void {
    file_put_contents($this->project . '/composer.json', 'not json');

    $command = composeCommand($this->project)->withInput();

    expect($command->callPlatformFromComposerJson())->toBeNull();
});

it('returns null from platformFromComposerJson when no composer.json exists', function (): void {
    $command = composeCommand($this->project)->withInput();

    expect($command->callPlatformFromComposerJson())->toBeNull();
});

dataset('default services per platform', [
    'magento' => ['magento', ['mysql', 'redis', 'opensearch']],
    'maho' => ['maho', ['mysql', 'redis']],
    'openmage' => ['openmage', ['mysql', 'redis']],
    'mageos' => ['mageos', ['mysql', 'redis', 'opensearch']],
]);

it('picks platform-specific default services', function (string $platform, array $expected): void {
    $command = composeCommand($this->project)->withInput(['--platform' => $platform]);

    expect($command->callDefaultServices())->toBe($expected);
})->with('default services per platform');

dataset('default php versions per platform', [
    'magento' => ['magento', '8.3'],
    'maho' => ['maho', '8.5'],
    'openmage' => ['openmage', '8.3'],
    'mageos' => ['mageos', '8.3'],
]);

it('picks platform-specific default php versions', function (string $platform, string $expected): void {
    $command = composeCommand($this->project)->withInput(['--platform' => $platform]);

    expect($command->callPhpVersion())->toBe($expected);
})->with('default php versions per platform');

it('lets an explicit --php option override the platform default', function (): void {
    $command = composeCommand($this->project)->withInput(['--platform' => 'maho', '--php' => '8.2']);

    expect($command->callPhpVersion())->toBe('8.2');
});

dataset('application service names per platform', [
    'magento' => ['magento', 'magento.test'],
    'maho' => ['maho', 'maho.test'],
    'openmage' => ['openmage', 'openmage.test'],
    'mageos' => ['mageos', 'mageos.test'],
]);

it('picks the platform-specific application service name when no compose file exists', function (string $platform, string $expected): void {
    $command = composeCommand($this->project)->withInput(['--platform' => $platform]);

    expect($command->callApplicationServiceName())->toBe($expected);
})->with('application service names per platform');

it('prefers an application service already present in the compose file over the platform default', function (): void {
    $command = composeCommand($this->project)->withInput(['--platform' => 'magento']);

    $detected = $command->callApplicationServiceFromCompose([
        'services' => [
            'mysql' => ['image' => 'mysql:8.4'],
            'openmage.test' => ['image' => 'corsair-8.3/app'],
            'magento.test' => ['image' => 'corsair-8.3/app'],
        ],
    ]);

    expect($detected)->toBe('openmage.test');
});

it('returns null when applicationServiceFromCompose finds no known application service', function (): void {
    $command = composeCommand($this->project)->withInput();

    expect($command->callApplicationServiceFromCompose(['services' => ['mysql' => ['image' => 'mysql:8.4']]]))->toBeNull()
        ->and($command->callApplicationServiceFromCompose(['services' => 'not-an-array']))->toBeNull();
});

it('adds a new key when setEnvValue does not find an existing one', function (): void {
    $command = composeCommand($this->project)->withInput();

    $result = $command->callSetEnvValue("APP_PORT=80\n", 'DB_HOST', 'mysql');

    expect($result)->toBe("APP_PORT=80\nDB_HOST=mysql\n");
});

it('replaces an existing key in place with setEnvValue', function (): void {
    $command = composeCommand($this->project)->withInput();

    $result = $command->callSetEnvValue("DB_HOST=127.0.0.1\nDB_PORT=3306\n", 'DB_HOST', 'mysql');

    expect($result)->toBe("DB_HOST=mysql\nDB_PORT=3306\n");
});

it('ships sensible default .env contents', function (): void {
    $command = composeCommand($this->project)->withInput();

    expect($command->callDefaultEnvContents())
        ->toContain('DB_DATABASE=magento')
        ->toContain('REDIS_HOST=127.0.0.1');
});

it('reads an environment value from a map-style service environment', function (): void {
    $command = composeCommand($this->project)->withInput();

    expect($command->callEnvironmentValue(['CORSAIR_PLATFORM' => 'maho'], 'CORSAIR_PLATFORM'))->toBe('maho');
});

it('reads an environment value from a list-style KEY=value service environment', function (): void {
    $command = composeCommand($this->project)->withInput();

    expect($command->callEnvironmentValue(['WWWUSER=1000', 'CORSAIR_PLATFORM=openmage'], 'CORSAIR_PLATFORM'))->toBe('openmage');
});

it('returns null for an environment value that is not present or not an array', function (): void {
    $command = composeCommand($this->project)->withInput();

    expect($command->callEnvironmentValue(['WWWUSER=1000'], 'CORSAIR_PLATFORM'))->toBeNull()
        ->and($command->callEnvironmentValue(null, 'CORSAIR_PLATFORM'))->toBeNull();
});

it('resolves the compose path to the first existing known compose file name', function (): void {
    file_put_contents($this->project . '/docker-compose.yml', 'services: {}');

    $command = composeCommand($this->project)->withInput();

    expect($command->callComposePath())->toBe($this->project . '/docker-compose.yml');
});

it('defaults the compose path to compose.yaml when nothing exists yet', function (): void {
    $command = composeCommand($this->project)->withInput();

    expect($command->callComposePath())->toBe($this->project . '/compose.yaml');
});

it('resolves stub and package paths relative to the package root', function (): void {
    $command = composeCommand($this->project)->withInput();

    expect($command->callStubPath('compose.stub'))
        ->toBe(dirname(__DIR__, 4) . '/resources/stubs/compose.stub')
        ->and(file_exists($command->callStubPath('compose.stub')))->toBeTrue()
        ->and($command->callPackagePath('resources/runtimes'))
        ->toBe(dirname(__DIR__, 4) . '/resources/runtimes');
});
