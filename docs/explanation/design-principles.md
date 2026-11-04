# Design principles

These are the principles Corsair's design is checked against when a change is being considered.

**One environment per project.** Services, networks, volumes, and configuration stay isolated per project so Magento 1, Magento 2, OpenMage, Mage-OS, and Maho projects can coexist on the same machine without colliding on ports, database names, or container names.

**Explicit compatibility.** Runtime and service versions are chosen for the Magento release actually being developed (see the [platform support matrix](../reference/platforms.md)) instead of offering one universal stack that happens to work for some combination of versions.

**Reproducible onboarding.** Versioned configuration (`compose.yaml`, `.env`, the published Docker files) and containerized dependencies are preferred over manual workstation setup, so a new contributor's environment matches everyone else's.

**A familiar workflow.** Corsair generates a standard Docker Compose project. Nothing it adds hides `docker compose` itself — you can always drop to `docker compose exec`, `docker compose logs`, or edit `compose.yaml` directly when Corsair's own commands aren't enough. This mirrors [Laravel Sail](https://github.com/laravel/sail)'s approach for Laravel.

**Development first.** The generated configuration is a local development environment, not a production deployment template. In particular, legacy Magento 1 and end-of-life PHP versions are meant to be isolated on the developer's machine and must not be exposed directly to the internet.
