# How to install Corsair on an OpenMage project

[OpenMage](https://github.com/OpenMage/magento-lts) is the actively maintained Magento 1 LTS fork. It requires PHP 8.1 or later. From an OpenMage checkout:

```bash
composer require maco-studios/corsair --dev
vendor/bin/corsair install --platform=openmage
vendor/bin/corsair up -d
```

Corsair detects `openmage` automatically from the `openmage/magento-lts` package in `composer.json` when `--platform` is omitted. Passing a `--php` version earlier than `8.1` makes `install` fail outright.

The web server uses the project root as the document root (OpenMage's classic layout), and only `index.php`, `api.php`, and `cron.php` are executed through PHP — every other path under `app/`, `includes/`, `lib/`, `pkginfo/`, and `shell/` is denied at the web server.

OpenMage has no single CLI entry point like `bin/magento` or `./maho` — it ships individual scripts under `shell/`. Get a shell in the app container with `vendor/bin/corsair shell` and run them directly, or run them one-off without an interactive session:

```bash
vendor/bin/corsair shell
php shell/indexer.php reindexall
php shell/compiler.php compile

# or, without an interactive session:
docker compose exec openmage.test php shell/indexer.php reindexall
```

The scheduler container runs `./cron.php` every minute — that's what scheduled indexing, email, and other OpenMage cron jobs need, and it starts as soon as `cron.php` exists in the project root. OpenMage has no separate queue container, since it has no message queue to service.

## Known issue: `install.php` crashes before it creates `app/etc/local.xml`

As of `openmage/magento-lts` `^20.0.0`, running OpenMage's own CLI installer (`php install.php ...`) on a fresh, uninstalled project fails with:

```
PHP Fatal error:  Uncaught Error: Call to a member function fetchOne() on false
  in app/code/core/Mage/Core/Model/Config.php:529
```

This is a bug in OpenMage itself, not in Corsair or Docker: `Mage_Core_Model_Config::getCacheSaveLock()` and `releaseCacheSaveLock()` try to acquire a MySQL `GET_LOCK()` whenever config caching is enabled — which it is by default, even before the application has ever been installed. With no `local.xml` yet, there's no database connection to use, `getConnection()` returns `false`, and calling `->fetchOne()` on `false` is a fatal error. The same class already guards other cache code with `Mage::isInstalled()`; these two methods are just missing that same guard.

Work around it with a two-line patch via [`cweagans/composer-patches`](https://github.com/cweagans/composer-patches) (which `openmage/magento-lts` already pulls in transitively), applied before requiring `openmage/magento-lts`:

```bash
composer config --json extra.patches '{"openmage/magento-lts": {"Guard getCacheSaveLock/releaseCacheSaveLock with Mage::isInstalled()": "vendor/maco-studios/corsair/resources/patches/openmage-install-cache-lock-guard.patch"}}'
composer require "openmage/magento-lts:^20.0.0"
```

Corsair ships that patch at `resources/patches/openmage-install-cache-lock-guard.patch`. It only needs to be in place for the initial `composer require`/`install.php` run — once `app/etc/local.xml` exists, the bug can't trigger again (that's exactly what `Mage::isInstalled()` checks).

See the [CLI reference](../reference/cli-commands.md) for every `install` option.
