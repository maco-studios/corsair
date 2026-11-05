<?php

/**
 * Copyright (c) 2026 Marcos "Marcão" Aurelio
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/maco-studios/corsair
 */

use Symfony\Component\Process\Process;

/**
 * Copyright (c) 2026 Marcos "Marcão" Aurelio
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/maco-studios/corsair
 */

$versions = ['8.0', '8.1', '8.2', '8.3', '8.4', '8.5'];
$root = dirname(__DIR__, 2);

it('ships the same openmage nginx and entrypoint for every php runtime', function () use ($versions, $root): void {
    $nginx = file_get_contents($root . '/resources/runtimes/8.3/conf/nginx-openmage.conf');
    $entrypoint = file_get_contents($root . '/resources/runtimes/8.3/start-container');

    expect($nginx)
        ->toContain('root /var/www/html;')
        ->toContain('location ~ ^/(app|includes|lib|pkginfo|shell|var)/')
        ->toContain('location ~ ^/api/rest(/|$)')
        ->toContain('location ~ ^/api/(soap|v2_soap|xmlrpc|jsonrpc)(/|$)')
        ->toContain('/api.php?type=rest')
        ->toContain('fastcgi_param HTTP_AUTHORIZATION $http_authorization;')
        ->toContain('fastcgi_pass fastcgi_backend;');

    expect($entrypoint)
        ->toContain('CORSAIR_PLATFORM:-magento')
        ->toContain('nginx-openmage.conf')
        ->not->toContain('cron.php');

    foreach ($versions as $version) {
        $runtime = $root . '/resources/runtimes/' . $version;

        expect(file_get_contents($runtime . '/conf/nginx-openmage.conf'))->toBe($nginx)
            ->and(file_get_contents($runtime . '/start-container'))->toBe($entrypoint)
            ->and(file_get_contents($runtime . '/Dockerfile'))->toContain('COPY conf/nginx-openmage.conf /opt/corsair/nginx-openmage.conf');
    }
});

it('applies the OpenMage 20.18 install patch without fuzz', function () use ($root): void {
    $project = temporaryProjectPath('openmage-patch');
    $sourcePath = $project . '/app/code/core/Mage/Core/Model/Config.php';
    mkdir(dirname($sourcePath), 0755, true);
    file_put_contents(
        $sourcePath,
        <<<'PHP'
<?php

class Mage_Core_Model_Config extends Mage_Core_Model_Config_Base
{
    /**
     * @throws Exception
     * @SuppressWarnings("PHPMD.ExitExpression")
     */
    public function getCacheSaveLock($waitTime = null, $ignoreFailure = false)
    {
        if (!Mage::app()->useCache('config')) {
            return;
        }

        $waitTime = $waitTime ?: (getenv('MAGE_CONFIG_CACHE_LOCK_WAIT') ?: (PHP_SAPI === 'cli' ? 60 : 3));
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        if (!$connection) {
            return;
        }
    }

    /**
     * Release the cache saving lock after it is saved or no longer needed
     *
     * @return void
     */
    public function releaseCacheSaveLock()
    {
        if (!Mage::app()->useCache('config')) {
            return;
        }

        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        if (!$connection) {
            return;
        }
    }
}
PHP,
    );

    try {
        $patch = new Process(
            ['patch', '--dry-run', '--fuzz=0', '-p1'],
            $project,
        );
        $patch->setInput(file_get_contents($root . '/resources/patches/openmage-install-cache-lock-guard.patch'));
        $patch->mustRun();

        expect($patch->getOutput())->toContain('checking file app/code/core/Mage/Core/Model/Config.php');
    } finally {
        removeDirectoryRecursively($project);
    }
});
