# Set up OpenMage

This tutorial creates a local Docker environment for an OpenMage project and verifies the legacy application scripts inside the container.

Corsair does not install OpenMage or configure the application. Follow the [OpenMage project documentation](https://openmage.org/) for the supported release, Composer setup, installer, and application configuration.

## 1. Prepare the project

Create or check out the OpenMage project using the official instructions, then install Corsair:

```bash
cd path/to/openmage-project
composer require maco-studios/corsair --dev
```

## 2. Generate and start the environment

```bash
vendor/bin/corsair install --platform=openmage
vendor/bin/corsair up -d
vendor/bin/corsair ps
```

The application service is `openmage.test`. OpenMage defaults to MySQL and Redis. The minimum PHP runtime enforced by Corsair is PHP 8.1.

## 3. Run the official installer

OpenMage is Magento 1-compatible and does not provide `bin/magento`; Corsair therefore has no `vendor/bin/corsair magento setup:install` equivalent for it. Follow the [official OpenMage documentation](https://openmage.org/) and use its `install.php` installer from inside the application container.

This is the same installation path exercised by Corsair's OpenMage E2E test. Enter the application container, load `.env`, and run the installer:

```bash
vendor/bin/corsair shell

set -a
. ./.env
set +a

php install.php \
  --license_agreement_accepted yes \
  --locale en_US \
  --timezone UTC \
  --default_currency USD \
  --db_host "${DB_HOST}" \
  --db_name "${DB_DATABASE}" \
  --db_user "${DB_USERNAME}" \
  --db_pass "${DB_PASSWORD}" \
  --url "http://127.0.0.1:${APP_PORT:-80}/" \
  --skip_url_validation yes \
  --use_rewrites yes \
  --use_secure no \
  --secure_base_url '' \
  --use_secure_admin no \
  --admin_firstname Corsair \
  --admin_lastname Admin \
  --admin_email admin@example.com \
  --admin_username admin \
  --admin_password '<password>'

exit
```

Replace the admin password and keep `DB_HOST` as the Docker service name (`mysql` or `mariadb`). The `127.0.0.1` in `--url` is the host-reachable base URL used by the browser; it is not the database host.

## 4. Run legacy scripts

OpenMage has no `bin/magento` entry point. Open a shell and run the project scripts:

```bash
vendor/bin/corsair shell
php shell/indexer.php reindexall
php shell/compiler.php compile
```

