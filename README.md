<p align="center">
  <img src=".github/assets/img/logo.png" alt="Corsair" width="180">
</p>

<h1 align="center">Corsair</h1>

<p align="center">
  <a href="LICENSE"><img src=".github/assets/img/badges/license-mit.svg" alt="MIT License"></a>
  <img src=".github/assets/img/badges/magento-1.svg" alt="Magento 1">
  <img src=".github/assets/img/badges/magento-2.svg" alt="Magento 2">
  <img src=".github/assets/img/badges/docker-compose.svg" alt="Docker Compose">
  <img src=".github/assets/img/badges/pest-5.svg" alt="Pest 5">
</p>

<p align="center"><strong>A Docker-powered development environment for Magento 1 and Magento 2.</strong></p>

Corsair brings the simplicity of [Laravel Sail](https://github.com/laravel/sail) to Magento development. It uses **Docker, Docker Compose, and Bash** to help developers work with different Magento projects without installing and managing each project's PHP, database, and supporting services directly on their machines.

## Introduction

Magento development often involves incompatible runtime requirements: legacy Magento 1 applications may rely on older PHP and database versions, while Magento 2 applications require their own compatible PHP, database, and search services.

Corsair is designed to make these environments **isolated, reproducible, and easy to operate**. The goal is a consistent local workflow for starting, stopping, inspecting, and working on Magento projects—without having to rebuild your workstation for every store.

Like Laravel Sail, Corsair focuses on the developer experience rather than on hiding the underlying containers. You can use familiar Docker Compose commands whenever you need finer control.

## Magento Support

| | Magento 1 | Magento 2 |
| --- | --- | --- |
| PHP | Legacy, version-matched PHP runtime | Version-matched PHP runtime |
| Web server | Apache or Nginx, according to project configuration | Apache or Nginx, according to project configuration |
| Database | Compatible MySQL or MariaDB version | Compatible MySQL or MariaDB version |
| Cache | Redis, where configured | Redis, where configured |
| Search | Project-specific integrations | Elasticsearch or OpenSearch, where required by the Magento release |
| Tooling | Magento shell scripts, Composer where applicable | Composer and `bin/magento` |

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

### Working with Magento 2

With the correct PHP service and working directory configured:

```bash
docker compose exec <php-service> php bin/magento cache:flush
docker compose exec <php-service> php bin/magento indexer:reindex
docker compose exec <php-service> php bin/magento setup:upgrade
```

Replace `<php-service>` with the actual PHP service name in your Compose file. Magento commands assume the container's working directory is the Magento application root.

## Design Principles

**One environment per project.** Keep application services, networks, volumes, and configuration isolated so Magento 1 and Magento 2 projects can coexist.

**Explicit compatibility.** Choose runtime and service versions for the Magento release being developed instead of relying on a universal stack.

**Reproducible onboarding.** Prefer versioned configuration and containerized dependencies over manual workstation setup.

**A familiar workflow.** Use simple shell tooling and standard Docker Compose commands, with direct access to the underlying services when needed.

**Development first.** Local configuration is not a production deployment template. In particular, legacy Magento 1 and end-of-life PHP versions should be isolated and must not be exposed directly to the internet.

## Documentation

- [Laravel Sail](https://github.com/laravel/sail) — inspiration for the lightweight Docker-based developer experience.
- [Docker Compose documentation](https://docs.docker.com/compose/) — service lifecycle and container configuration.
- [Adobe Commerce / Magento documentation](https://experienceleague.adobe.com/en/docs/commerce) — Magento 2 installation and system requirements.

## Contributing

Contributions, bug reports, and compatibility improvements are welcome. When reporting a problem, include your host OS, Docker/Compose versions, Magento version, selected PHP version, and relevant container logs. Never include store credentials or other secrets.

## License

This project is licensed under the [MIT License](LICENSE).
