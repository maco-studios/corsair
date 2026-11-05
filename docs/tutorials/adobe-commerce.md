# Set up Adobe Commerce / Magento 2

This tutorial creates a local Docker environment for an Adobe Commerce or Magento 2 project and verifies that its application CLI runs inside the container.

Corsair does not install the application itself. For the application package, authentication keys, system requirements, and `setup:install` options, follow the [official Adobe Commerce installation documentation](https://experienceleague.adobe.com/en/docs/commerce-operations/installation-guide/overview).

## 1. Install Corsair

From the project root:

```bash
composer require maco-studios/corsair --dev
```

## 2. Generate the local environment

```bash
vendor/bin/corsair install --platform=magento
```

Accept the default services, or select them explicitly. The default Magento 2 selection is MySQL, Redis, and OpenSearch. This writes `compose.yaml` and `.env`, resolves a PHP runtime, and prepares the application image.

## 3. Start the containers

```bash
vendor/bin/corsair up -d
vendor/bin/corsair ps
```

The application service is `magento.test` and is served on `http://localhost` by default.

## 4. Install the application

The official Adobe Commerce or Magento 2 installer is still the authority for all options. The following is a basic local installation command that runs through Corsair and reads the database credentials generated in `.env`:

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

Replace the admin password and confirm the application-specific options against the [official Adobe Commerce installation documentation](https://experienceleague.adobe.com/en/docs/commerce-operations/installation-guide/overview). If you selected Elasticsearch instead of OpenSearch, use that service's installer options instead.

For Magento 2 commands after installation:

```bash
vendor/bin/corsair magento cache:flush
vendor/bin/corsair magento indexer:reindex
```

Continue with [the development workflow](../how-to/development-workflow.md).

