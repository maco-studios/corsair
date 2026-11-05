# Adopt an existing project

Use this guide when a Magento-family application already has source code, Composer dependencies, and possibly an existing Compose setup.

## Back up local configuration

Commit or copy existing Compose and environment files before running Corsair. Corsair updates a detected Compose file and writes values into `.env`.

## Install and generate

```bash
composer require maco-studios/corsair --dev
vendor/bin/corsair install --platform=openmage
```

Run the platform explicitly when the project contains ambiguous Composer packages.

## Reconcile application configuration

Inside the containers, service names are Docker hostnames. Update application configuration to use `mysql`, `redis`, `opensearch`, or `rabbitmq`, rather than host-loopback addresses.

## Start and validate

```bash
vendor/bin/corsair up -d
vendor/bin/corsair ps
vendor/bin/corsair logs -f
```

Follow the platform's official migration procedure for database imports, configuration generation, and application upgrades.

