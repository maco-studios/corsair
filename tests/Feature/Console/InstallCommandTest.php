<?php

/**
 * Copyright (c) 2026 Marcos "Marcão" Aurelio
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/maco-studios/corsair
 */

use Corsair\Console\InstallCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;

beforeEach(function (): void {
    putenv('CORSAIR_SKIP_DOCKER=1');

    $this->project = temporaryProjectPath('corsair-install');
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
    putenv('CORSAIR_SKIP_DOCKER');
});

it('installs compose services and env defaults non-interactively', function (): void {
    $command = (new InstallCommand())->setProjectPath($this->project);
    $tester = new CommandTester($command);

    $status = $tester->execute([
        '--with' => 'mysql,redis,opensearch',
        '--php' => '8.3',
    ], ['interactive' => false]);

    expect($status)->toBe(0)
        ->and(file_exists($this->project . '/compose.yaml'))->toBeTrue()
        ->and(file_exists($this->project . '/.env'))->toBeTrue();

    $compose = Yaml::parseFile($this->project . '/compose.yaml');

    expect($compose['services'])->toHaveKeys(['magento.test', 'scheduler', 'queue', 'mysql', 'redis', 'opensearch'])
        ->and($compose['services']['magento.test']['depends_on'])->toContain('mysql', 'redis', 'opensearch')
        ->and($compose['services']['scheduler']['depends_on'])->toContain('mysql', 'redis', 'opensearch')
        ->and($compose['services']['scheduler']['environment']['CORSAIR_PLATFORM'])->toBe('magento')
        ->and($compose['services']['scheduler']['build'])->toBe($compose['services']['magento.test']['build'])
        ->and($compose['services']['scheduler'])->not->toHaveKey('ports')
        ->and($compose['services']['scheduler']['command'][2])->toContain('bin/magento cron:run')
        ->and($compose['services']['scheduler']['restart'])->toBe('unless-stopped')
        ->and($compose['services']['queue']['command'][2])->toContain('queue:consumers:start')
        ->and($compose['services']['queue'])->not->toHaveKey('ports')
        ->and($compose['volumes'])->toHaveKeys(['corsair-mysql', 'corsair-redis', 'corsair-opensearch'])
        ->and($compose['services']['mysql']['volumes'])->toContain(
            './vendor/maco-studios/corsair/resources/database/mysql/create-testing-database.sh:/docker-entrypoint-initdb.d/10-create-testing-database.sh',
        );

    $env = file_get_contents($this->project . '/.env');

    expect($env)
        ->toContain('DB_HOST=mysql')
        ->toContain('REDIS_HOST=redis')
        ->toContain('OPENSEARCH_HOST=opensearch');
});

it('installs a maho stack on php 8.5', function (): void {
    $command = (new InstallCommand())->setProjectPath($this->project);
    $tester = new CommandTester($command);

    $status = $tester->execute([
        '--platform' => 'maho',
        '--devcontainer' => true,
    ], ['interactive' => false]);

    expect($status)->toBe(0)
        ->and($tester->getDisplay())->toContain('maho:install');

    $compose = Yaml::parseFile($this->project . '/compose.yaml');

    expect($compose['services'])->toHaveKeys(['maho.test', 'scheduler', 'queue', 'mysql', 'redis'])
        ->and($compose['services'])->not->toHaveKey('opensearch')
        ->and($compose['services'])->not->toHaveKey('magento.test')
        ->and($compose['services']['maho.test']['depends_on'])->toContain('mysql', 'redis')
        ->and($compose['services']['maho.test']['environment']['CORSAIR_PLATFORM'])->toBe('maho')
        ->and($compose['services']['maho.test']['build']['context'])->toContain('/8.5')
        ->and($compose['services']['scheduler']['command'][2])->toContain('cron:run default')
        ->and($compose['services']['queue']['command'][2])->toContain('queue:work --exclusive')
        ->and($compose['services']['mysql']['image'])->toBe('mysql:8.4')
        ->and($compose['services']['redis']['command'])->toContain('volatile-lfu');

    $env = file_get_contents($this->project . '/.env');
    $devcontainer = file_get_contents($this->project . '/.devcontainer/devcontainer.json');

    expect($env)
        ->toContain('DB_HOST=mysql')
        ->toContain('DB_DATABASE=maho')
        ->toContain('CORSAIR_PLATFORM=maho')
        ->and($devcontainer)->toContain('"service": "maho.test"')
        ->and($devcontainer)->toContain('Corsair Maho');
});

it('warns when maho is installed on php 8.4', function (): void {
    $command = (new InstallCommand())->setProjectPath($this->project);
    $tester = new CommandTester($command);

    $status = $tester->execute([
        '--platform' => 'maho',
        '--php' => '8.4',
        '--with' => 'mysql',
    ], ['interactive' => false]);

    $compose = Yaml::parseFile($this->project . '/compose.yaml');

    expect($status)->toBe(0)
        ->and($tester->getDisplay())->toContain('PHP 8.4 only runs Maho 26.9')
        ->and($compose['services']['maho.test']['build']['context'])->toContain('/8.4');
});

it('pins mariadb 11.8 for a maho stack', function (): void {
    $command = (new InstallCommand())->setProjectPath($this->project);
    $tester = new CommandTester($command);

    $status = $tester->execute([
        '--platform' => 'maho',
        '--with' => 'mariadb,redis',
    ], ['interactive' => false]);

    $compose = Yaml::parseFile($this->project . '/compose.yaml');
    $env = file_get_contents($this->project . '/.env');

    expect($status)->toBe(0)
        ->and($compose['services']['mariadb']['image'])->toBe('mariadb:11.8')
        ->and($env)->toContain('DB_HOST=mariadb');
});

it('rejects maho on php older than 8.3', function (): void {
    $command = (new InstallCommand())->setProjectPath($this->project);
    $tester = new CommandTester($command);

    $status = $tester->execute([
        '--platform' => 'maho',
        '--php' => '8.2',
    ], ['interactive' => false]);

    expect($status)->toBe(1)
        ->and(file_exists($this->project . '/compose.yaml'))->toBeFalse();
});

it('rejects an unknown platform', function (): void {
    $command = (new InstallCommand())->setProjectPath($this->project);
    $tester = new CommandTester($command);

    $status = $tester->execute([
        '--platform' => 'oscommerce',
    ], ['interactive' => false]);

    expect($status)->toBe(1)
        ->and(file_exists($this->project . '/compose.yaml'))->toBeFalse();
});

it('installs an openmage stack', function (): void {
    $command = (new InstallCommand())->setProjectPath($this->project);
    $tester = new CommandTester($command);

    $status = $tester->execute([
        '--platform' => 'openmage',
    ], ['interactive' => false]);

    expect($status)->toBe(0);

    $compose = Yaml::parseFile($this->project . '/compose.yaml');

    expect($compose['services'])->toHaveKeys(['openmage.test', 'scheduler', 'mysql', 'redis'])
        ->and($compose['services'])->not->toHaveKey('opensearch')
        ->and($compose['services'])->not->toHaveKey('magento.test')
        ->and($compose['services'])->not->toHaveKey('queue')
        ->and($compose['services']['openmage.test']['depends_on'])->toContain('mysql', 'redis')
        ->and($compose['services']['openmage.test']['environment']['CORSAIR_PLATFORM'])->toBe('openmage')
        ->and($compose['services']['openmage.test']['build']['context'])->toContain('/8.3')
        ->and($compose['services']['scheduler']['command'][2])->toContain('cron.php');

    $env = file_get_contents($this->project . '/.env');

    expect($env)
        ->toContain('DB_HOST=mysql')
        ->toContain('DB_DATABASE=openmage')
        ->toContain('CORSAIR_PLATFORM=openmage');
});

it('rejects openmage on php older than 8.1', function (): void {
    $command = (new InstallCommand())->setProjectPath($this->project);
    $tester = new CommandTester($command);

    $status = $tester->execute([
        '--platform' => 'openmage',
        '--php' => '8.0',
    ], ['interactive' => false]);

    expect($status)->toBe(1)
        ->and(file_exists($this->project . '/compose.yaml'))->toBeFalse();
});

it('detects the openmage platform from composer.json', function (): void {
    file_put_contents($this->project . '/composer.json', json_encode([
        'require' => ['openmage/magento-lts' => '^20.0'],
    ]));

    $command = (new InstallCommand())->setProjectPath($this->project);
    $tester = new CommandTester($command);

    $status = $tester->execute([
        '--with' => 'none',
    ], ['interactive' => false]);

    expect($status)->toBe(0);

    $compose = Yaml::parseFile($this->project . '/compose.yaml');

    expect($compose['services'])->toHaveKey('openmage.test')
        ->and($compose['services']['openmage.test']['environment']['CORSAIR_PLATFORM'])->toBe('openmage');
});

it('installs a mageos stack', function (): void {
    $command = (new InstallCommand())->setProjectPath($this->project);
    $tester = new CommandTester($command);

    $status = $tester->execute([
        '--platform' => 'mageos',
        '--with' => 'mysql,redis,opensearch',
    ], ['interactive' => false]);

    expect($status)->toBe(0);

    $compose = Yaml::parseFile($this->project . '/compose.yaml');

    expect($compose['services'])->toHaveKeys(['mageos.test', 'mysql', 'redis', 'opensearch'])
        ->and($compose['services'])->not->toHaveKey('magento.test')
        ->and($compose['services']['mageos.test']['depends_on'])->toContain('mysql', 'redis', 'opensearch')
        ->and($compose['services']['mageos.test']['environment']['CORSAIR_PLATFORM'])->toBe('mageos');

    $env = file_get_contents($this->project . '/.env');

    expect($env)
        ->toContain('DB_HOST=mysql')
        ->toContain('DB_DATABASE=mageos')
        ->toContain('CORSAIR_PLATFORM=mageos');
});

it('detects the mageos platform from composer.json', function (): void {
    file_put_contents($this->project . '/composer.json', json_encode([
        'require' => ['mage-os/mageos-magento2' => '^1.0'],
    ]));

    $command = (new InstallCommand())->setProjectPath($this->project);
    $tester = new CommandTester($command);

    $status = $tester->execute([
        '--with' => 'none',
    ], ['interactive' => false]);

    expect($status)->toBe(0);

    $compose = Yaml::parseFile($this->project . '/compose.yaml');

    expect($compose['services'])->toHaveKey('mageos.test')
        ->and($compose['services']['mageos.test']['environment']['CORSAIR_PLATFORM'])->toBe('mageos');
});

it('rejects invalid services', function (): void {
    $command = (new InstallCommand())->setProjectPath($this->project);
    $tester = new CommandTester($command);

    $status = $tester->execute([
        '--with' => 'mysql,not-a-service',
    ], ['interactive' => false]);

    expect($status)->toBe(1)
        ->and(file_exists($this->project . '/compose.yaml'))->toBeFalse();
});
