<p align="center">
  <img src=".github/assets/img/logo.png" alt="Corsair" width="180">
</p>

<h1 align="center">Corsair</h1>

<p align="center">
  <a href="LICENSE"><img src=".github/assets/img/badges/license-mit.svg" alt="MIT License"></a>
  <img src=".github/assets/img/badges/magento.svg" alt="Magento 1, Magento 2, OpenMage, Mage-OS, and MahoCommerce">
</p>

<p align="center"><strong>A Docker-powered development environment for Magento 1, Magento 2, OpenMage, Mage-OS, and MahoCommerce.</strong></p>

Corsair brings the simplicity of [Laravel Sail](https://github.com/laravel/sail) to Magento, [OpenMage](https://github.com/OpenMage/magento-lts), [Mage-OS](https://github.com/mage-os/mageos-magento2), and [MahoCommerce](https://mahocommerce.com/) development. It uses **Docker, Docker Compose, and Bash** to help developers work with different projects without installing and managing each project's PHP, database, and supporting services directly on their machines.

## Introduction

Magento development often involves incompatible runtime requirements: legacy Magento 1 applications may rely on older PHP and database versions, while Magento 2 applications require their own compatible PHP, database, and search services.

Corsair is designed to make these environments **isolated, reproducible, and easy to operate**. The goal is a consistent local workflow for starting, stopping, inspecting, and working on Magento projects—without having to rebuild your workstation for every store.

Like Laravel Sail, Corsair focuses on the developer experience rather than on hiding the underlying containers. You can use familiar Docker Compose commands whenever you need finer control.

## Magento Support

| | Magento 1 | Magento 2 | OpenMage | Mage-OS | MahoCommerce |
| --- | --- | --- | --- | --- | --- |
| PHP | Legacy, version-matched PHP runtime | Version-matched PHP runtime | PHP 8.1 or later | Version-matched PHP runtime | PHP 8.5 by default. PHP 8.3 and 8.4 remain available for Maho 26.9 |
| Web server | Apache or Nginx, according to project configuration | Apache or Nginx, according to project configuration | Nginx, document root the project root | Apache or Nginx, document root `pub/` | Nginx, document root `public/` |
| Database | Compatible MySQL or MariaDB version | Compatible MySQL or MariaDB version | MySQL or MariaDB | Compatible MySQL or MariaDB version | MySQL 8.4 or MariaDB 11.8 |
| Cache | Redis, where configured | Redis, where configured | Redis, where configured | Redis, where configured | Redis, with `maxmemory-policy volatile-lfu` |
| Search | Project-specific integrations | Elasticsearch or OpenSearch, where required by the Magento release | MySQL, included with the database service | Elasticsearch or OpenSearch, where required by the release | MySQL, included with the database service |
| Tooling | Magento shell scripts, Composer where applicable | Composer and `bin/magento` | Magento 1 shell scripts, Composer where applicable | Composer and `bin/magento` | `./maho`, via `vendor/bin/corsair maho` |

**Compatibility is project-specific.** A Magento version is supported only when its chosen PHP, database, search engine, extensions, and container images are compatible. The table describes supported environment types, not a guarantee that every possible version combination works.

## Requirements

- Docker Engine with Docker Compose, or Docker Desktop with Compose enabled.
- Git.
- Linux, macOS, or Windows using WSL2 for a Linux-based development workflow.

PHP, Composer, MySQL, Redis, and other application dependencies do not need to be installed on the host when their corresponding services are provided by the project's containers.

## Getting Started

Choose the configuration appropriate to your Magento project and review its environment settings, especially the **Magento version, PHP version, exposed ports, database credentials, and mounted source directory**.

From the directory containing the project's Compose configuration, standard Docker commands include:

```bash
# Start the development environment
docker compose up -d

# Inspect running services
docker compose ps

# Follow service logs
docker compose logs -f

# Stop the environment
docker compose down
```

These are standard Docker Compose commands. The exact Corsair setup process, configuration files, service names, and any convenience CLI commands depend on the implementation in this repository.

### Working with Magento 1

Once the PHP service and Magento source mount are configured, you can run Magento 1's maintenance scripts inside the appropriate container. For example:

```bash
docker compose exec <php-service> php shell/indexer.php reindexall
```

### Working with OpenMage

[OpenMage](https://github.com/OpenMage/magento-lts) is the actively maintained Magento 1 LTS fork. It requires PHP 8.1 or later. From an OpenMage checkout:

```bash
composer require maco-studios/corsair --dev
vendor/bin/corsair corsair:install --platform=openmage
docker compose up -d
```

Corsair detects the `openmage` platform automatically from `composer.json` (the `openmage/magento-lts` package) when `--platform` is omitted. The web server uses the project root as the document root, matching OpenMage's classic layout, and only `index.php`, `api.php`, and `cron.php` are executed.

Run OpenMage's shell scripts the same way you would any other command inside the container:

```bash
docker compose exec openmage.test php shell/indexer.php reindexall
docker compose exec openmage.test php shell/compiler.php compile
```

The application container runs `./cron.php` every minute, which is what scheduled indexing, email, and other OpenMage cron jobs need. The loop waits until `cron.php` exists.

### Working with Mage-OS

[Mage-OS](https://github.com/mage-os/mageos-magento2) is a community-maintained, drop-in compatible distribution of Magento 2 Open Source. It uses the same `pub/` document root, `bin/magento` CLI, and PHP/database/search requirements as Magento 2.

```bash
composer require maco-studios/corsair --dev
vendor/bin/corsair corsair:install --platform=mageos
docker compose up -d
docker compose exec mageos.test php bin/magento setup:install ...
```

Corsair detects the `mageos` platform automatically from `composer.json` (any `mage-os/*` package) when `--platform` is omitted. Run `bin/magento` commands the same way you would for Magento 2:

```bash
docker compose exec mageos.test php bin/magento cache:flush
docker compose exec mageos.test php bin/magento indexer:reindex
```

### Working with Magento 2

With the correct PHP service and working directory configured:

```bash
docker compose exec <php-service> php bin/magento cache:flush
docker compose exec <php-service> php bin/magento indexer:reindex
docker compose exec <php-service> php bin/magento setup:upgrade
```

Replace `<php-service>` with the actual PHP service name in your Compose file. Magento commands assume the container's working directory is the Magento application root.

### Working with MahoCommerce

Maho 26.11 requires PHP 8.5 or later. Maho 26.9 is the last release that runs on PHP 8.3 and 8.4. Corsair selects PHP 8.5 unless you pass `--php`. The web server uses `public/` as the document root, and only `index.php`, `rest.php`, and `api.php` are executed.

From an empty directory:

```bash
composer create-project mahocommerce/maho-starter .
composer require maco-studios/corsair --dev
vendor/bin/corsair corsair:install --platform=maho
docker compose up -d
vendor/bin/corsair maho:install --sample-data
```

`maho:install` reads `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` from `.env` and runs `./maho install` inside `maho.test`. It then reindexes and flushes the cache. The default admin user is `admin` / `corsair-password1`.

Run any other Maho command the same way:

```bash
vendor/bin/corsair maho cache:flush
vendor/bin/corsair maho index:reindex:all
vendor/bin/corsair maho cron:list
```

The application container runs `./maho cron:run default` and `./maho cron:run always` every minute, which is what the message queue and resized images need. The loop waits until `./maho` exists.

The installer leaves cache and sessions on the filesystem. The Redis service already uses `maxmemory-policy volatile-lfu`, which Maho's cache backend requires. To store cache and sessions in Redis, add this inside `<global>` in `app/etc/local.xml`:

```xml
<cache>
    <backend>redis</backend>
    <backend_options>
        <dsn>redis://redis/0</dsn>
    </backend_options>
</cache>
<session_save>redis</session_save>
<redis_session>
    <dsn>redis://redis/1</dsn>
</redis_session>
```

Add Mailpit when you want to catch outgoing mail: `vendor/bin/corsair corsair:add mailpit`.

## Design Principles

**One environment per project.** Keep application services, networks, volumes, and configuration isolated so Magento 1, Magento 2, OpenMage, Mage-OS, and Maho projects can coexist.

**Explicit compatibility.** Choose runtime and service versions for the Magento release being developed instead of relying on a universal stack.

**Reproducible onboarding.** Prefer versioned configuration and containerized dependencies over manual workstation setup.

**A familiar workflow.** Use simple shell tooling and standard Docker Compose commands, with direct access to the underlying services when needed.

**Development first.** Local configuration is not a production deployment template. In particular, legacy Magento 1 and end-of-life PHP versions should be isolated and must not be exposed directly to the internet.

## Documentation

- [Laravel Sail](https://github.com/laravel/sail) — inspiration for the lightweight Docker-based developer experience.
- [Docker Compose documentation](https://docs.docker.com/compose/) — service lifecycle and container configuration.
- [Adobe Commerce / Magento documentation](https://experienceleague.adobe.com/en/docs/commerce) — Magento 2 installation and system requirements.
- [OpenMage documentation](https://openmage.github.io/) — installation, system requirements, and shell scripts.
- [Mage-OS documentation](https://mage-os.org/) — installation and system requirements for the Magento 2-compatible distribution.
- [MahoCommerce getting started](https://mahocommerce.com/about/getting-started/) — PHP, database, and `./maho install`.
- [Maho web server configuration](https://mahocommerce.com/hosting/web-server/) — document root and API routing.
- [Maho Redis](https://mahocommerce.com/hosting/redis/) — cache and session DSN values.

## Contributing

Contributions, bug reports, and compatibility improvements are welcome. When reporting a problem, include your host OS, Docker/Compose versions, Magento version, selected PHP version, and relevant container logs. Never include store credentials or other secrets.

## License

This project is licensed under the [MIT License](LICENSE).
