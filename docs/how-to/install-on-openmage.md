# How to install Corsair on an OpenMage project

[OpenMage](https://github.com/OpenMage/magento-lts) is the actively maintained Magento 1 LTS fork. It requires PHP 8.1 or later. From an OpenMage checkout:

```bash
composer require maco-studios/corsair --dev
vendor/bin/corsair corsair:install --platform=openmage
vendor/bin/corsair up -d
```

Corsair detects `openmage` automatically from the `openmage/magento-lts` package in `composer.json` when `--platform` is omitted. Passing a `--php` version earlier than `8.1` makes `corsair:install` fail outright.

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

See the [CLI reference](../reference/cli-commands.md) for every `corsair:install` option.
