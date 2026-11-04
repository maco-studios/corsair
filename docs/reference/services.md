# Services

The services selectable via `--with` (on `corsair:install`) or as the argument to `corsair:add`.

| Service | Image | Default forwarded port | `.env` keys Corsair sets |
| --- | --- | --- | --- |
| `mysql` | `mysql:8.4` | `3306` (`FORWARD_DB_PORT`) | `DB_HOST=mysql`, `DB_PORT=3306`, `DB_USERNAME=corsair`, `DB_PASSWORD=password`, `DB_DATABASE=<platform default>` |
| `mariadb` | `mariadb:11` (`mariadb:11.8` when `--platform=maho`) | `3306` (`FORWARD_DB_PORT`) | Same keys as `mysql`, with `DB_HOST=mariadb` |
| `redis` | `redis:alpine` | `6379` (`FORWARD_REDIS_PORT`) | `REDIS_HOST=redis`, `REDIS_PORT=6379` |
| `elasticsearch` | `docker.elastic.co/elasticsearch/elasticsearch:8.11.4` | `9200` (`FORWARD_ELASTICSEARCH_PORT`) | `ELASTICSEARCH_HOST=elasticsearch`, `ELASTICSEARCH_PORT=9200` |
| `opensearch` | `opensearchproject/opensearch:2` | `9200` (`FORWARD_OPENSEARCH_PORT`) | `OPENSEARCH_HOST=opensearch`, `OPENSEARCH_PORT=9200` |
| `rabbitmq` | `rabbitmq:4-management-alpine` | `5672` app / `15672` dashboard | `RABBITMQ_HOST=rabbitmq`, `RABBITMQ_PORT=5672`, `RABBITMQ_USER=corsair`, `RABBITMQ_PASSWORD=password`, `RABBITMQ_VHOST=/` |
| `mailpit` | `axllent/mailpit:latest` | `1025` SMTP / `8025` dashboard | `MAIL_HOST=mailpit`, `MAIL_PORT=1025` |
| `varnish` | `varnish:7.5` | `8080` (`FORWARD_VARNISH_PORT`) → container port `80` | `VARNISH_HOST=varnish`, `VARNISH_PORT=80` |

Notes:

- `mysql` and `mariadb` both create a database named `${DB_DATABASE}` for user `corsair` / password `password`, and both mount a healthcheck and a `create-testing-database.sh` init script.
- `--with=none` (or the `none` argument to `corsair:add`) installs no services at all.
- Any of `mysql`, `mariadb`, `redis`, `elasticsearch`, `opensearch`, and `rabbitmq` get a named Docker volume (`corsair-<service>`) for persistence; `mailpit` and `varnish` do not.
- Services you add are appended to the app service's `depends_on`, and to the scheduler/queue containers' `depends_on` — see [Scheduler and queue containers](../explanation/scheduler-and-queue-containers.md).
- Forward ports are overridable per project via the matching `FORWARD_*_PORT` environment variable in `.env`; they are not written there automatically.
