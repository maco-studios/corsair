# CLI reference

Run commands from the project root with `vendor/bin/corsair`.

| Command | Purpose |
| --- | --- |
| `install` | Create or update Compose and `.env`; optionally build a runtime and Dev Container |
| `add <services>` | Add services to an existing installation |
| `publish` | Copy runtime and database files into `./docker` |
| `shell [shell]` | Open a shell in the application container |
| `composer <args>` | Run Composer in the application container |
| `npm <args>` | Run npm in the application container |
| `db:query` | Open the configured database client |
| `db:dump [file]` | Dump the configured database |
| `db:import <file>` | Import an SQL file |
| `magento <args>` | Run `bin/magento` for Magento 2 and Mage-OS |
| `maho <args>` | Run `./maho` for MahoCommerce |

Arguments that are not Corsair commands are forwarded to `docker compose -f <compose-file>`:

```bash
vendor/bin/corsair up -d
vendor/bin/corsair logs -f
vendor/bin/corsair exec redis redis-cli ping
```

## Install options

```text
--with=<services>       Comma-separated services, or none
--platform=<platform>   magento, maho, openmage, or mageos
--php=<version>         8.0 through 8.5
--devcontainer          Write .devcontainer/devcontainer.json
```

Set `CORSAIR_SKIP_DOCKER=1` to print generated commands without running Docker.

