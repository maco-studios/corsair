# How to install Corsair on a Magento 2 project

From the Magento 2 project root:

```bash
composer require maco-studios/corsair --dev
vendor/bin/corsair install
vendor/bin/corsair up -d
```

Corsair detects the `magento` platform automatically from the `magento/*` package in `composer.json`, so `--platform` can be omitted. It installs `mysql`, `redis`, and `opensearch` by default when `--with` is omitted and the terminal isn't interactive; pass `--with` to choose a different set, or answer the interactive prompt.

Run `bin/magento` through the `magento` shortcut, the same way `vendor/bin/corsair maho` works for MahoCommerce:

```bash
vendor/bin/corsair magento cache:flush
vendor/bin/corsair magento indexer:reindex
vendor/bin/corsair magento setup:upgrade
```

That's equivalent to `docker compose exec magento.test php bin/magento <args>`, if you'd rather run it directly.

See the [CLI reference](../reference/cli-commands.md) for every `install` option, and [Services](../reference/services.md) for the full list of services you can add with `--with`.
