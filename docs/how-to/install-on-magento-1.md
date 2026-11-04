# How to install Corsair on a Magento 1 project

From the Magento 1 project root:

```bash
composer require maco-studios/corsair --dev
vendor/bin/corsair install --platform=magento
vendor/bin/corsair up -d
```

Corsair doesn't auto-detect classic Magento 1 (there's no `magento/*` Composer package to key off), so pass `--platform=magento` explicitly, or answer the interactive prompt with `magento` if you omit it.

Magento 1 has no `bin/magento`-style entry point — it ships individual scripts under `shell/`. Get a shell in the app container with `vendor/bin/corsair shell` and run them directly, or run them one-off:

```bash
vendor/bin/corsair shell
php shell/indexer.php reindexall

# or, without an interactive session:
docker compose exec magento.test php shell/indexer.php reindexall
```

See the [CLI reference](../reference/cli-commands.md) for every `install` option, and [Services](../reference/services.md) for the database/cache/search services you can add with `--with`.
