# Install Corsair

Use this guide when the application is already present and you only need to add Corsair's local Docker environment.

## Install the development dependency

```bash
composer require maco-studios/corsair --dev
```

## Generate Compose and environment files

For an interactive setup:

```bash
vendor/bin/corsair install
```

For a repeatable setup:

```bash
vendor/bin/corsair install \
  --platform=magento \
  --php=8.3 \
  --with=mysql,redis,opensearch
```

Corsair detects the platform from `composer.json` when `--platform` is omitted and detects a compatible PHP runtime from Composer metadata when `--php` is omitted.

## Start the environment

```bash
vendor/bin/corsair up -d
vendor/bin/corsair ps
```

The application installer remains platform-specific. Continue with the relevant tutorial: [Adobe Commerce / Magento 2](../tutorials/adobe-commerce.md), [Mage-OS](../tutorials/mage-os.md), [OpenMage](../tutorials/openmage.md), or [MahoCommerce](../tutorials/mahocommerce.md).

## Non-interactive installation

Use `--with=none` to create only the application service, or provide a comma-separated service list. Non-interactive installations otherwise use platform defaults.

