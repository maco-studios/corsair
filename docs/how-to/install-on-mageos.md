# How to install Corsair on a Mage-OS project

[Mage-OS](https://github.com/mage-os/mageos-magento2) is a community-maintained, drop-in compatible distribution of Magento 2 Open Source. It uses the same `pub/` document root, `bin/magento` CLI, and PHP/database/search requirements as Magento 2.

```bash
composer require maco-studios/corsair --dev
vendor/bin/corsair install --platform=mageos
vendor/bin/corsair up -d
```

Corsair detects `mageos` automatically from any `mage-os/*` package in `composer.json` when `--platform` is omitted.

Run `bin/magento` commands the same way you would for Magento 2, through the `magento` shortcut:

```bash
vendor/bin/corsair magento cache:flush
vendor/bin/corsair magento indexer:reindex
```

See the [CLI reference](../reference/cli-commands.md) for every `install` option, and [Services](../reference/services.md) for the full list of services you can add with `--with`.
