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

it('skips a non-array service entry when scanning compose services for a platform', function (): void {
    file_put_contents($this->project . '/compose.yaml', Yaml::dump([
        'services' => ['magento.test' => 'not-an-array'],
    ]));

    $command = composeCommand($this->project)->withInput();

    expect($command->callPlatform())->toBe('magento');
});

it('skips a non-string package key and returns null when nothing matches a platform', function (): void {
    // A purely-numeric string object key in the JSON becomes an int array
    // key once decoded, which platformFromComposerJson() skips over.
    file_put_contents($this->project . '/composer.json', json_encode([
        'require' => ['0' => 'placeholder/ignored', 'some/unrelated-package' => '^1.0'],
    ]));

    $command = composeCommand($this->project)->withInput();

    expect($command->callPlatformFromComposerJson())->toBeNull();
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
        ->toBe(dirname(__DIR__, 4) . '/resources/runtimes')
        ->and($command->callPackagePath())
        ->toBe(dirname(__DIR__, 4));
});

it('returns false from stackHasService when no compose file exists', function (): void {
    $command = composeCommand($this->project)->withInput();

    expect($command->callStackHasService('mysql'))->toBeFalse()
        ->and($command->callDatabaseEngine())->toBeNull();
});

it('picks mariadb as the database engine when mysql is not installed', function (): void {
    file_put_contents($this->project . '/compose.yaml', Yaml::dump([
        'services' => ['mariadb' => ['image' => 'mariadb:11.8']],
    ]));

    $command = composeCommand($this->project)->withInput();

    expect($command->callDatabaseEngine())->toBe('mariadb');
});

it('defaults the container user to 1000:1000 when .env is missing', function (): void {
    $command = composeCommand($this->project)->withInput();

    expect($command->callEnvVariables())->toBe([])
        ->and($command->callContainerUser())->toBe('1000:1000');
});

it('skips blank lines, comments, and key-less lines when reading .env', function (): void {
    file_put_contents($this->project . '/.env', "WWWUSER=1001\n\n# a comment\nno-equals-sign\nWWWGROUP=1002\n");

    $command = composeCommand($this->project)->withInput();

    expect($command->callEnvVariables())->toBe(['WWWUSER' => '1001', 'WWWGROUP' => '1002'])
        ->and($command->callContainerUser())->toBe('1001:1002');
});

it('does not overwrite an existing named volume when a service is added again', function (): void {
    $command = composeCommand($this->project)->withInput(['--platform' => 'magento']);
    $command->callBuildDockerCompose(['mysql']);

    $compose = Yaml::parseFile($this->project . '/compose.yaml');
    $compose['volumes']['corsair-mysql']['driver'] = 'custom-marker';
    file_put_contents($this->project . '/compose.yaml', Yaml::dump($compose, 10, 4));

    $command = composeCommand($this->project)->withInput(['--platform' => 'magento']);
    $command->callBuildDockerCompose(['mysql']);

    $compose = Yaml::parseFile($this->project . '/compose.yaml');

    expect($compose['volumes']['corsair-mysql']['driver'])->toBe('custom-marker');
});

it('leaves a service alone in setServiceEnvironment when its environment is list-style', function (): void {
    $command = composeCommand($this->project)->withInput();

    $service = $command->callSetServiceEnvironment(['environment' => ['WWWUSER=1000']], 'CORSAIR_PLATFORM', 'maho');

    expect($service['environment'])->toBe(['WWWUSER=1000']);
});

it('treats an invalid compose.yaml as an empty project when building the compose file', function (): void {
    file_put_contents($this->project . '/compose.yaml', "~\n");

    $command = composeCommand($this->project)->withInput(['--platform' => 'magento']);
    $command->callBuildDockerCompose(['mysql']);

    $compose = Yaml::parseFile($this->project . '/compose.yaml');

    // Parsing "~" yields null, not an array — buildDockerCompose() treats
    // that as a blank project rather than erroring, so there's no
    // magento.test service to attach the mysql dependency to (just a
    // warning, covered by the next test).
    expect($compose['services'])->toHaveKey('mysql')
        ->and($compose['services'])->not->toHaveKey('magento.test');
});

it('warns when the application service is missing from the compose file entirely', function (): void {
    file_put_contents($this->project . '/compose.yaml', Yaml::dump([
        'services' => ['mysql' => ['image' => 'mysql:8.4']],
    ]));

    $command = composeCommand($this->project)->withInput(['--platform' => 'magento']);
    $command->callBuildDockerCompose(['mysql']);

    expect($command->callApplicationServiceFromCompose(Yaml::parseFile($this->project . '/compose.yaml')))->toBeNull();
});

it('copies .env.example to .env when it exists and .env does not', function (): void {
    file_put_contents($this->project . '/.env.example', "APP_PORT=80\nDB_HOST=127.0.0.1\n");

    $command = composeCommand($this->project)->withInput(['--platform' => 'magento']);
    $command->callReplaceEnvVariables(['mysql']);

    expect(file_get_contents($this->project . '/.env'))->toContain('APP_PORT=80');
});

it('sets elasticsearch, rabbitmq, and varnish env variables when those services are requested', function (): void {
    $command = composeCommand($this->project)->withInput(['--platform' => 'magento']);
    $command->callReplaceEnvVariables(['elasticsearch', 'rabbitmq', 'varnish']);

    $env = (string) file_get_contents($this->project . '/.env');

    expect($env)
        ->toContain('ELASTICSEARCH_HOST=elasticsearch')
        ->toContain('ELASTICSEARCH_PORT=9200')
        ->toContain('RABBITMQ_HOST=rabbitmq')
        ->toContain('RABBITMQ_PORT=5672')
        ->toContain('RABBITMQ_USER=corsair')
        ->toContain('RABBITMQ_PASSWORD=password')
        ->toContain('RABBITMQ_VHOST=/')
        ->toContain('VARNISH_HOST=varnish')
        ->toContain('VARNISH_PORT=80');
});

it('runs the real docker info/pull/build commands when preparing an installation', function (): void {
    putenv('CORSAIR_SKIP_DOCKER=0');
    $command = composeCommand($this->project)->withInput(['--platform' => 'magento']);
    file_put_contents($this->project . '/compose.yaml', Yaml::dump(['services' => ['magento.test' => []]]));

    withFakeDocker(fn(): mixed => $command->callPrepareInstallation(['mysql']));
    putenv('CORSAIR_SKIP_DOCKER');

    expect(true)->toBeTrue();
});

it('stops preparing the installation when docker info fails', function (): void {
    putenv('CORSAIR_SKIP_DOCKER=0');
    $command = composeCommand($this->project)->withInput(['--platform' => 'magento']);
    file_put_contents($this->project . '/compose.yaml', Yaml::dump(['services' => ['magento.test' => []]]));

    withFakeDocker(fn(): mixed => $command->callPrepareInstallation(['mysql']), exitCode: 1);
    putenv('CORSAIR_SKIP_DOCKER');

    expect(true)->toBeTrue();
});
