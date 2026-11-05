# Platforms

| Platform flag | Application service | Database name | Document root | CLI |
| --- | --- | --- | --- | --- |
| `magento` | `magento.test` | `magento` | `pub/` for Magento 2; project root for Magento 1 | `magento` for Magento 2; shell scripts for Magento 1 |
| `mageos` | `mageos.test` | `mageos` | `pub/` | `magento` |
| `openmage` | `openmage.test` | `openmage` | project root | shell scripts |
| `maho` | `maho.test` | `maho` | `public/` | `maho` |

Corsair detects `magento/*`, `mage-os/*`, `openmage/*`, and `mahocommerce/*` packages in `composer.json`. An existing Compose file with `CORSAIR_PLATFORM` takes precedence.

The `magento` flag covers both Magento 1 and Magento 2. The application layout and official installation procedure remain platform-specific.
