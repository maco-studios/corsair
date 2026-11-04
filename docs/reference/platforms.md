# Platform support matrix

`--platform` accepts one of `magento`, `maho`, `openmage`, `mageos`. This table describes what each value configures; it does not guarantee every PHP, database, or search engine combination works with every release of that platform.

| | `magento` | `maho` | `openmage` | `mageos` |
| --- | --- | --- | --- | --- |
| Represents | Magento 1 and Magento 2 | MahoCommerce | OpenMage (Magento 1 LTS) | Mage-OS (Magento 2-compatible) |
| Detected from `composer.json` | `magento/*` package | `mahocommerce/*` package | `openmage/*` package | `mage-os/*` package |
| Default PHP version | `8.3` | `8.5` | `8.3` (minimum `8.1`) | `8.3` |
| Minimum PHP version | none enforced | `8.3` (Maho 26.11 needs `8.5`; installing with `<8.5` only runs Maho 26.9, with a warning) | `8.1` (enforced; install fails below it) | none enforced |
| App container name | `magento.test` | `maho.test` | `openmage.test` | `mageos.test` |
| Web server document root | `pub/` (Magento 2) or project root (Magento 1), per project config | `public/` | project root | `pub/` |
| Entry points served | per project | `index.php`, `rest.php`, `api.php` | `index.php`, `api.php`, `cron.php` | per project |
| Default database name | `magento` | `maho` | `openmage` | `mageos` |
| Scheduler container command | `php bin/magento cron:run`, every 60s | `php ./maho cron:run default` then `cron:run always`, every 60s | `php ./cron.php`, every 60s | `php bin/magento cron:run`, every 60s |
| Queue container command | starts every `bin/magento queue:consumers:list` consumer, every 60s | `php ./maho queue:work --exclusive`, every 5s | none | starts every `bin/magento queue:consumers:list` consumer, every 60s |
| Install CLI | project-specific (`bin/magento setup:install`, Magento 1 installer) | project-specific (`vendor/bin/corsair maho install`) | project-specific | `bin/magento setup:install` |
| CLI shortcut | `vendor/bin/corsair magento <args>` (Magento 2 only — Magento 1 has no `bin/magento`, use `shell`) | `vendor/bin/corsair maho <args>` | none — use `vendor/bin/corsair shell` and run `shell/*.php` scripts directly | `vendor/bin/corsair magento <args>` |

`openmage` and `maho` get a Nginx config (`nginx-openmage.conf`, `nginx-maho.conf`) that locks down the classic Magento 1 directory layout (`app/`, `includes/`, `lib/`, `pkginfo/`, `shell/`, `var/`) and routes only the entry points above through PHP; `magento` and `mageos` use the standard `pub/`-rooted config. See [Platform detection](../explanation/platform-detection.md) for how the value is resolved when `--platform` is omitted.
