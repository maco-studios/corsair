# Set up Mage-OS

This tutorial creates a local Docker environment for a Mage-OS project and runs its Magento-compatible CLI through Corsair.

Corsair does not create or install Mage-OS. Use the [Mage-OS project documentation](https://mage-os.org/) for the official package, requirements, authentication, application installation, and upgrade procedure.

## 1. Prepare the project

Create or check out the Mage-OS project using the official instructions, then install Corsair:

```bash
cd path/to/mageos-project
composer require maco-studios/corsair --dev
```

## 2. Generate the environment

```bash
vendor/bin/corsair install --platform=mageos
```

The application service is `mageos.test`. The default services are MySQL, Redis, and OpenSearch.

## 3. Start the stack

```bash
vendor/bin/corsair up -d
vendor/bin/corsair ps
```

## 4. Install Mage-OS

Mage-OS uses the Magento 2 CLI. This basic local command runs through Corsair and reads the database and OpenSearch credentials from `.env`:

```bash
set -a
. ./.env
set +a

vendor/bin/corsair magento setup:install \
  --base-url="http://localhost:${APP_PORT:-80}/" \
  --db-host="${DB_HOST}" \
  --db-name="${DB_DATABASE}" \
  --db-user="${DB_USERNAME}" \
  --db-password="${DB_PASSWORD}" \
  --admin-firstname=Store \
  --admin-lastname=Admin \
  --admin-email=admin@example.com \
  --admin-user=admin \
  --admin-password='<password>' \
  --language=en_US \
  --currency=USD \
  --timezone=America/Sao_Paulo \
  --use-rewrites=1 \
  --backend-frontname=admin \
  --search-engine=opensearch \
  --opensearch-host="${OPENSEARCH_HOST}" \
  --opensearch-port="${OPENSEARCH_PORT}" \
  --opensearch-enable-auth=0 \
  --opensearch-ssl-enable=0
```

Replace the admin password and confirm the complete option set against the [Mage-OS project documentation](https://mage-os.org/).

## 5. Run application commands

```bash
vendor/bin/corsair magento --version
vendor/bin/corsair magento cache:flush
vendor/bin/corsair magento setup:upgrade
```

