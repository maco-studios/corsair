<p align="center">
  <img src=".github/assets/img/logo.png" alt="Corsair" width="180">
</p>

<h1 align="center">Corsair</h1>

<p align="center">
  <a href="LICENSE"><img src=".github/assets/img/badges/license-mit.svg" alt="MIT License"></a>
  <img src=".github/assets/img/badges/platform-magento.svg" alt="Magento 1 and 2">
  <img src=".github/assets/img/badges/platform-openmage.svg" alt="OpenMage">
  <img src=".github/assets/img/badges/platform-mageos.svg" alt="Mage-OS">
  <img src=".github/assets/img/badges/platform-maho.svg" alt="MahoCommerce">
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

**Compatibility is project-specific.** A Magento version is supported only when its chosen PHP, database, search engine, extensions, and container images are compatible. See the [platform support matrix](docs/reference/platforms.md) for the full detail behind this table.

## Requirements

- Docker Engine with Docker Compose, or Docker Desktop with Compose enabled.
- Git.
- Linux, macOS, or Windows using WSL2 for a Linux-based development workflow.

PHP, Composer, MySQL, Redis, and other application dependencies do not need to be installed on the host when their corresponding services are provided by the project's containers.

## Quickstart

```bash
composer require maco-studios/corsair --dev
vendor/bin/corsair corsair:install
vendor/bin/corsair up -d
```

`corsair:install` detects your platform (Magento, Maho, OpenMage, or Mage-OS) from `composer.json`, writes `compose.yaml` and `.env`, and builds the application image. From there, `vendor/bin/corsair` aliases `docker compose` the same way Laravel Sail does, so you can keep using it for everything else:

```bash
vendor/bin/corsair ps        # inspect running services
vendor/bin/corsair logs -f   # follow service logs
vendor/bin/corsair down      # stop the environment
```

(Plain `docker compose ...` commands work exactly the same, if you prefer them.)

New to Corsair? Follow the [getting-started tutorial](docs/tutorials/getting-started.md) for a full walkthrough, from an empty directory to a logged-in admin. Already have a project? Jump straight to the how-to guide for your platform: [Magento 1](docs/how-to/install-on-magento-1.md), [Magento 2](docs/how-to/install-on-magento-2.md), [OpenMage](docs/how-to/install-on-openmage.md), [Mage-OS](docs/how-to/install-on-mageos.md), or [MahoCommerce](docs/how-to/install-on-maho.md).

## Documentation

Full documentation lives in [`docs/`](docs/README.md), organized as tutorials, how-to guides, reference, and explanation:

- **[Tutorial](docs/tutorials/getting-started.md)** — set up a MahoCommerce store from scratch.
- **How-to guides** — [install per platform](docs/README.md), [add a service](docs/how-to/add-a-service.md), [publish and customize the Docker files](docs/how-to/publish-docker-files.md), [use a dev container](docs/how-to/use-a-devcontainer.md), [enable Xdebug](docs/how-to/enable-xdebug.md).
- **Reference** — [CLI commands](docs/reference/cli-commands.md), [services](docs/reference/services.md), [platform support matrix](docs/reference/platforms.md), [environment variables](docs/reference/environment-variables.md).
- **Explanation** — [design principles](docs/explanation/design-principles.md), [platform detection](docs/explanation/platform-detection.md), [scheduler and queue containers](docs/explanation/scheduler-and-queue-containers.md).

External references: [Laravel Sail](https://github.com/laravel/sail) (inspiration for this project), [Docker Compose documentation](https://docs.docker.com/compose/), [OpenMage documentation](https://openmage.github.io/), [Mage-OS documentation](https://mage-os.org/), [MahoCommerce getting started](https://mahocommerce.com/about/getting-started/).

## Contributing

Contributions, bug reports, and compatibility improvements are welcome. When reporting a problem, include your host OS, Docker/Compose versions, Magento version, selected PHP version, and relevant container logs. Never include store credentials or other secrets.

## License

This project is licensed under the [MIT License](LICENSE).
