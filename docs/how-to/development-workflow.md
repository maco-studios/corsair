# Use the development workflow

Corsair runs application tooling inside the containers, where the PHP version and extensions match the generated runtime.

## Run application commands

Magento 2 and Mage-OS:

```bash
vendor/bin/corsair magento cache:flush
vendor/bin/corsair magento setup:upgrade
```

MahoCommerce:

```bash
vendor/bin/corsair maho cache:flush
```

Magento 1 and OpenMage use scripts rather than a unified CLI:

```bash
vendor/bin/corsair shell
php shell/indexer.php reindexall
```

## Run Composer and npm

```bash
vendor/bin/corsair composer install
vendor/bin/corsair npm run build
```

## Open a shell

```bash
vendor/bin/corsair shell
vendor/bin/corsair shell sh
```

## Manage the database

```bash
vendor/bin/corsair db:query
vendor/bin/corsair db:dump backups/local.sql
vendor/bin/corsair db:import backups/local.sql
```

## Manage the stack

Commands not owned by Corsair are passed to Docker Compose:

```bash
vendor/bin/corsair up -d
vendor/bin/corsair restart magento.test
vendor/bin/corsair logs -f
vendor/bin/corsair down
```

