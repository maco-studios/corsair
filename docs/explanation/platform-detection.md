# Platform detection

When `--platform` is omitted, Corsair resolves the platform in this order:

1. An explicit `--platform` option
2. `CORSAIR_PLATFORM` in an existing Compose service
3. A Maho application service in an existing Compose file
4. Recognized package names in `composer.json`
5. An interactive prompt
6. `magento` in a non-interactive process

Recognized Composer prefixes are `mahocommerce/`, `openmage/`, `mage-os/`, and `magento/`.

Use the explicit option when a project contains multiple platform-related packages or when adopting a legacy project.
