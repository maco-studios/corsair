# Set up MahoCommerce

This tutorial creates a local Docker environment for MahoCommerce and runs the Maho CLI inside the application container.

Corsair does not install MahoCommerce. Follow the [MahoCommerce project documentation](https://mahocommerce.com/) for the official starter package, requirements, installer, and application configuration.

## 1. Create or check out the project

Prepare the project using MahoCommerce's official instructions, then install Corsair:

```bash
cd path/to/maho-project
composer require maco-studios/corsair --dev
```

## 2. Generate the environment

```bash
vendor/bin/corsair install --platform=maho
```

The application service is `maho.test`. Corsair selects MySQL and Redis by default and serves Maho from `public/`.

Maho requires PHP 8.3 or later. Maho 26.11 requires PHP 8.5 or later; Corsair warns when an older runtime is selected.

## 3. Start the stack

```bash
vendor/bin/corsair up -d
vendor/bin/corsair ps
```

## 4. Run Maho's official installer

Use Maho's own installer through Corsair. This command reads the database credentials generated in `.env`:

```bash
set -a
. ./.env
set +a

vendor/bin/corsair maho install \
  --license_agreement_accepted=yes \
  --db_host="${DB_HOST}" \
  --db_name="${DB_DATABASE}" \
  --db_user="${DB_USERNAME}" \
  --db_pass="${DB_PASSWORD}" \
  --url="http://localhost:${APP_PORT:-80}/" \
  --secure_base_url="http://localhost:${APP_PORT:-80}/" \
  --use_secure=0 \
  --use_secure_admin=0 \
  --admin_firstname=Store \
  --admin_lastname=Admin \
  --admin_email=admin@example.com \
  --admin_username=admin \
  --admin_password='<password>' \
  --sample_data=1
```

Replace the admin password, remove `--sample_data=1` if you do not want sample data, and confirm the exact options against the [official MahoCommerce documentation](https://mahocommerce.com/) and `vendor/bin/corsair maho install --help`.

## 5. Run application commands

```bash
vendor/bin/corsair maho cache:flush
vendor/bin/corsair maho index:reindex:all
```

