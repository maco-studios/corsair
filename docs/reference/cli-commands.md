# CLI commands

All commands are run through `vendor/bin/corsair`, from the root of the project you installed Corsair into.

## Docker Compose alias

Any argument that isn't one of Corsair's own commands below is forwarded as-is to `docker compose -f <compose-file>`, the same way `vendor/bin/sail` aliases `docker-compose` for Laravel. This means:

```bash
vendor/bin/corsair up -d
vendor/bin/corsair down
vendor/bin/corsair ps
vendor/bin/corsair logs -f
```

are equivalent to running `docker compose up -d`, `docker compose down`, `docker compose ps`, and `docker compose logs -f` yourself, just shorter and from any directory where `vendor/bin/corsair` resolves. It requires a Compose file to already exist — run `install` first, or it fails with "No Compose file found."

Arguments that match a Corsair command name (`install`, `add`, `publish`, `maho`, `magento`, `shell`, `npm`, `composer`, `db:query`, `db:dump`, `db:import`), `list`, `help`, or that start with `-` (such as `--version`) are routed to Corsair's own console application instead, never forwarded to Docker.

## Container tooling

These proxy straight into the application container (or a service container, for the database commands) instead of going through `docker compose`, the way `sail shell`, `sail npm`, and `sail composer` do for Laravel. All of them require the Compose file to exist first (`install`).

### `shell`

```bash
vendor/bin/corsair shell [<shell>]
```

Opens an interactive shell in the app service (`magento.test`, `maho.test`, `openmage.test`, or `mageos.test`). Defaults to `bash`, which every runtime image ships; pass another binary (e.g. `sh`) if you need it.

Magento 1 and OpenMage have no single CLI entry point (no `bin/magento`, no `./maho`) — they ship individual scripts under `shell/`. Use `shell` to get in and run them directly, e.g.:

```bash
vendor/bin/corsair shell
php shell/indexer.php reindexall
php shell/compiler.php compile
```

or, without an interactive session: `docker compose exec openmage.test php shell/indexer.php reindexall`.

### `npm`

```bash
vendor/bin/corsair npm <args>...
```

Forwards every argument to `npm` inside the app container, e.g. `vendor/bin/corsair npm install` or `vendor/bin/corsair npm run build`. Node.js and npm are preinstalled in the runtime image.

### `composer`

```bash
vendor/bin/corsair composer <args>...
```

Forwards every argument to `composer` inside the app container, e.g. `vendor/bin/corsair composer require some/package`. Running it inside the container keeps dependency resolution aligned with the container's PHP version and extensions rather than the host's.

### `db:query`

```bash
vendor/bin/corsair db:query
```

Opens an interactive `mysql` (or `mariadb`, if that's the installed service) client against the installed database, using `DB_USERNAME`, `DB_PASSWORD`, and `DB_DATABASE` from `.env`. Fails if no Compose file exists yet, or if neither `mysql` nor `mariadb` is an installed service (`mysql` wins when both are).

### `db:dump`

```bash
vendor/bin/corsair db:dump [<destination>]
```

Dumps the installed database with `mysqldump` (or `mariadb-dump`) to `<destination>`, or to `<database>-<timestamp>.sql` in the project root when omitted. Runs with `docker compose exec -T` (no TTY), since this is a non-interactive stream to a file, not a terminal session.

### `db:import`

```bash
vendor/bin/corsair db:import <file.sql>
```

Pipes `<file.sql>` into `mysql` (or `mariadb`) against the installed database. Fails if the file doesn't exist. Like `db:dump`, this runs without a TTY.

## `install`

Creates or updates `compose.yaml`, writes `.env`, and (unless `CORSAIR_SKIP_DOCKER=1` is set) pulls and builds the images.

```bash
vendor/bin/corsair install [--with=<services>] [--platform=<platform>] [--php=<version>] [--devcontainer]
```

| Option | Values | Default | Effect |
| --- | --- | --- | --- |
| `--with` | comma-separated list from [Services](services.md), or `none` | interactive prompt, or `mysql,redis,opensearch` (`mysql,redis` for `maho`/`openmage`) when the terminal isn't interactive | Services added to `compose.yaml` and wired into `.env` |
| `--platform` | `magento`, `maho`, `openmage`, `mageos` | detected from `composer.json`, then from an existing `compose.yaml`, then an interactive prompt, then `magento` | See [Platform detection](../explanation/platform-detection.md) |
| `--php` | `8.0`–`8.5` | detected from `composer.json`, then `8.5` for `maho`, `8.3` otherwise | PHP runtime image used for the app, scheduler, and queue containers. See [PHP version detection](../explanation/php-version-detection.md) |
| `--devcontainer` | flag | off | Writes `.devcontainer/devcontainer.json` and sets `WWWUSER`/`WWWGROUP` to `1000` in `.env` |

The command fails with a non-zero exit code if `--with` names an unsupported service, `--platform` names an unsupported platform, or `--php` is outside the supported range. It also fails for `maho` with PHP earlier than `8.3`, and for `openmage` with PHP earlier than `8.1`; it warns (without failing) when `maho` is installed with PHP earlier than `8.5`, since Maho 26.11 requires `8.5`.

## `add`

Adds services to an existing installation without touching the platform or PHP version.

```bash
vendor/bin/corsair add [services]
```

`services` is an optional, comma-separated argument (not an option), e.g. `vendor/bin/corsair add mailpit,varnish`. Pass `none` to add nothing. With no argument, it prompts interactively, or falls back to the same defaults as `install` when the terminal isn't interactive.

## `publish`

Copies the package's Docker runtime and database files from `vendor/maco-studios/corsair/resources/{runtimes,database}` into `./docker` in your project, and rewrites the `build.context` paths in `compose.yaml` to point at the copied files.

```bash
vendor/bin/corsair publish
```

Fails if no Compose file exists yet — run `install` first. Use this when you need to edit the Dockerfile, nginx config, `php.ini`, or `supervisord.conf` for your project instead of using the versions shipped with the package.

## `magento`

Forwards arguments to `bin/magento` inside the application container, for Magento 2 and Mage-OS (both ship `bin/magento`; Magento 1 and OpenMage don't — see [Container tooling](#container-tooling)'s `shell` for those).

```bash
vendor/bin/corsair magento <args>...
```

Equivalent to `docker compose exec magento.test php bin/magento <args>` (or `mageos.test`, whichever is the resolved app service). Fails if `bin/magento` doesn't exist in the project root, or if the Compose stack has neither a `magento.test` nor a `mageos.test` service. When `CORSAIR_SKIP_DOCKER=1` is set, it prints the command instead of running it. `vendor/bin/corsair list` only shows `magento` when `bin/magento` exists in the project — it still runs if you type it directly.

## `maho`

Forwards arguments to `./maho` inside the `maho.test` container.

```bash
vendor/bin/corsair maho <args>...
```

Equivalent to `docker compose exec maho.test php ./maho <args>`. Fails if `./maho` doesn't exist in the project root, or if the Compose stack has no `maho.test` service (run `install --platform=maho` first). When `CORSAIR_SKIP_DOCKER=1` is set, it prints the command instead of running it. `vendor/bin/corsair list` only shows `maho` when `./maho` exists in the project — it still runs if you type it directly.

Corsair doesn't wrap Maho's own application installer — install it yourself the same way you would `bin/magento setup:install` for Magento, using the database credentials `install` wrote to `.env` (`DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`):

```bash
vendor/bin/corsair maho install --license_agreement_accepted=yes --db_host=mysql --db_name=maho --db_user=corsair --db_pass=password --url=http://localhost/ --admin_username=admin --admin_password=<password>
vendor/bin/corsair maho index:reindex:all
vendor/bin/corsair maho cache:flush
```

See `vendor/bin/corsair maho install --help` for every option Maho's installer accepts.
