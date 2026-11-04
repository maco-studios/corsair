<p align="center">
  <img src=".github/assets/img/logo.png" alt="Corsair" width="180">
</p>

<h1 align="center">Corsair</h1>

<p align="center">
  <a href="LICENSE"><img src=".github/assets/img/badges/license-mit.svg" alt="MIT License"></a>
  <a href="https://github.com/maco-studios/corsair/actions/workflows/tests.yml"><img src=".github/assets/img/badges/coverage.svg" alt="Test coverage"></a>
  <a href="https://packagist.org/packages/maco-studios/corsair"><img src="https://img.shields.io/packagist/v/maco-studios/corsair" alt="Packagist version"></a>
</p>

<p align="center"><strong>Docker-powered local dev for Magento 1, Magento 2, OpenMage, Mage-OS, and MahoCommerce.</strong></p>

> **🚧 Under construction.** Corsair is early (pre-1.0) and still changing fast. Expect rough edges and breaking changes between releases — pin a version in `composer.json` rather than tracking `main`, and report anything broken via [issues](https://github.com/maco-studios/corsair/issues).

Corsair is [Laravel Sail](https://github.com/laravel/sail) for the Magento ecosystem: Docker, Docker Compose, and Bash, so you don't install PHP, MySQL, Redis, or search engines on your machine for every project. Each project gets its own isolated, reproducible environment, matched to whatever PHP, database, and services it actually needs.

## Quickstart

```bash
composer require maco-studios/corsair --dev
vendor/bin/corsair corsair:install
vendor/bin/corsair up -d
```

`corsair:install` detects your platform from `composer.json`, writes `compose.yaml` and `.env`, and builds the application image. From there, `vendor/bin/corsair` aliases `docker compose`:

```bash
vendor/bin/corsair ps        # inspect running services
vendor/bin/corsair logs -f   # follow service logs
vendor/bin/corsair down      # stop the environment
```

Plain `docker compose ...` commands work the same, if you prefer them.

## Requirements

- Docker Engine with Compose, or Docker Desktop with Compose enabled
- Git
- Linux, macOS, or Windows with WSL2

## Platform guides

New to Corsair? Start with the [getting-started tutorial](docs/tutorials/getting-started.md). Already have a project?

- [Magento 1](docs/how-to/install-on-magento-1.md)
- [Magento 2](docs/how-to/install-on-magento-2.md)
- [OpenMage](docs/how-to/install-on-openmage.md)
- [Mage-OS](docs/how-to/install-on-mageos.md)
- [MahoCommerce](docs/how-to/install-on-maho.md)

Support is project-specific — a given Magento version works only with compatible PHP, database, and search engine choices. See the [platform support matrix](docs/reference/platforms.md) for full detail.

## Documentation

Full docs live in [`docs/`](docs/README.md):

- **Tutorial** — [getting started](docs/tutorials/getting-started.md)
- **How-to** — [add a service](docs/how-to/add-a-service.md), [publish and customize Docker files](docs/how-to/publish-docker-files.md), [use a dev container](docs/how-to/use-a-devcontainer.md), [enable Xdebug](docs/how-to/enable-xdebug.md)
- **Reference** — [CLI commands](docs/reference/cli-commands.md), [services](docs/reference/services.md), [platform matrix](docs/reference/platforms.md), [environment variables](docs/reference/environment-variables.md)
- **Explanation** — [design principles](docs/explanation/design-principles.md), [platform detection](docs/explanation/platform-detection.md), [PHP version detection](docs/explanation/php-version-detection.md), [scheduler and queue containers](docs/explanation/scheduler-and-queue-containers.md)

## Contributing

Bug reports and compatibility improvements are welcome. Include your host OS, Docker/Compose versions, Magento version, and PHP version — never store credentials or other secrets.

## Support

If Corsair saves you time, consider [buying me a coffee](https://buymeacoffee.com/macosauresx).

## License

[MIT](LICENSE)
