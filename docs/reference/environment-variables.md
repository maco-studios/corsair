# Environment variables

`corsair:install` and `corsair:add` write to `.env` in the project root, creating it from `.env.example` (or a small built-in default) if it doesn't exist yet. These are the keys Corsair itself reads or writes; application-specific keys are untouched.

## Always set

| Key | Value | Set by |
| --- | --- | --- |
| `CORSAIR_PLATFORM` | the resolved platform (`magento`, `maho`, `openmage`, `mageos`) | `corsair:install` |
| `DB_USERNAME` | `corsair` | whenever `mysql` or `mariadb` is selected |
| `DB_PASSWORD` | `password` | whenever `mysql` or `mariadb` is selected |
| `DB_DATABASE` | the platform's default database name (see [Platforms](platforms.md)) | whenever `mysql` or `mariadb` is selected |

## Set per selected service

| Key(s) | Service |
| --- | --- |
| `DB_HOST=mysql`, `DB_PORT=3306` | `mysql` |
| `DB_HOST=mariadb`, `DB_PORT=3306` | `mariadb` |
| `REDIS_HOST=redis`, `REDIS_PORT=6379` | `redis` |
| `ELASTICSEARCH_HOST=elasticsearch`, `ELASTICSEARCH_PORT=9200` | `elasticsearch` |
| `OPENSEARCH_HOST=opensearch`, `OPENSEARCH_PORT=9200` | `opensearch` |
| `RABBITMQ_HOST=rabbitmq`, `RABBITMQ_PORT=5672`, `RABBITMQ_USER=corsair`, `RABBITMQ_PASSWORD=password`, `RABBITMQ_VHOST=/` | `rabbitmq` |
| `MAIL_HOST=mailpit`, `MAIL_PORT=1025` | `mailpit` |
| `VARNISH_HOST=varnish`, `VARNISH_PORT=80` | `varnish` |

If a value already exists in `.env`, Corsair overwrites it in place; otherwise it appends the key. A fresh `.env` also starts with any commented-out `# DB_*` lines uncommented when `mysql` or `mariadb` is selected.

## Read, not written, by Corsair

| Key | Used for | Default if unset |
| --- | --- | --- |
| `APP_PORT` | host port forwarded to the app container's port 80 | `80` |
| `WWWUSER`, `WWWGROUP` | UID/GID the app, scheduler, and queue containers run as | `1000` |
| `XDEBUG_MODE` | value substituted into `xdebug.mode` in the PHP image | `off` |
| `XDEBUG_CONFIG` | `xdebug.client_host`, used by IDEs expecting this variable | `client_host=host.docker.internal` |
| `FORWARD_DB_PORT`, `FORWARD_REDIS_PORT`, `FORWARD_ELASTICSEARCH_PORT`, `FORWARD_OPENSEARCH_PORT`, `FORWARD_RABBITMQ_PORT`, `FORWARD_RABBITMQ_DASHBOARD_PORT`, `FORWARD_MAILPIT_PORT`, `FORWARD_MAILPIT_DASHBOARD_PORT`, `FORWARD_VARNISH_PORT` | host ports forwarded to each service | the service's default port (see [Services](services.md)) |

`maho:install` additionally reads `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, and `APP_PORT` from `.env` to build the `./maho install` command.
