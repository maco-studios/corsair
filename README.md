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
vendor/bin/corsair install
vendor/bin/corsair up -d
```

`vendor/bin/corsair install` detects your platform from `composer.json`, writes `compose.yaml` and `.env`, and builds the application image. From there, `vendor/bin/corsair` aliases `docker compose`:

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

New to Corsair? Start with the [documentation home](docs/index.md) and choose the tutorial for [Adobe Commerce / Magento 2](docs/tutorials/adobe-commerce.md), [Mage-OS](docs/tutorials/mage-os.md), [OpenMage](docs/tutorials/openmage.md), or [MahoCommerce](docs/tutorials/mahocommerce.md).

Already have a project? Use the [existing-project guide](docs/how-to/adopt-existing-project.md). Support is project-specific; see the [platform reference](docs/reference/platforms.md) and [compatibility reference](docs/reference/compatibility.md).

## Documentation

The documentation is organized for Zensical using the Diátaxis framework. Preview or build it with `zensical serve` and `zensical build`:

- **Tutorials** — complete learning paths for each supported platform
- **How-to guides** — installation, adoption, services, development, customization, Xdebug, Dev Containers, and troubleshooting
- **Reference** — CLI, platforms, services, environment variables, generated files, and compatibility
- **Explanation** — architecture, detection, runtime selection, workers, and customization decisions

## Contributing

Bug reports and compatibility improvements are welcome. Include your host OS, Docker/Compose versions, Magento version, and PHP version — never store credentials or other secrets.

## Support

If Corsair saves you time, consider [buying me a coffee](https://buymeacoffee.com/macosauresx).

## Credits and attribution

Corsair includes portions adapted from the following MIT-licensed projects:

- [Laravel Sail](https://github.com/laravel/sail) — originally created by Taylor Otwell and Chris Fidao. The testing-database scripts are based on work by Jess Archer, with subsequent contributions from Bram, Tom Coonen, and Hafez Divandari. See the [Sail license](https://github.com/laravel/sail/blob/1.x/LICENSE.md).
- [docker-magento](https://github.com/markshust/docker-magento) — PHP Xdebug configuration based on the work maintained by Mark Shust and licensed under the copyright of Mage Inferno. See the [docker-magento license](https://github.com/markshust/docker-magento/blob/release/next/LICENSE.md).

The relevant resource files also contain inline attribution notices. Corsair's own code remains available under the [MIT license](LICENSE).

## License

[MIT](LICENSE)
