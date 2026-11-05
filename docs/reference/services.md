# Services

| Service | Image | Container port | Persistent volume |
| --- | --- | --- | --- |
| `mysql` | `mysql:8.4` | `3306` | yes |
| `mariadb` | `mariadb:11` (`11.8` for Maho) | `3306` | yes |
| `redis` | `redis:alpine` | `6379` | yes |
| `elasticsearch` | ElasticSearch `8.11.4` | `9200` | yes |
| `opensearch` | OpenSearch `2` | `9200` | yes |
| `rabbitmq` | RabbitMQ `4-management-alpine` | `5672`, dashboard `15672` | yes |
| `mailpit` | Mailpit `latest` | `1025`, dashboard `8025` | no |
| `varnish` | Varnish `7.5` | `80` | no |

Select services with `install --with=...` or add them later with `add ...`. Interactive installation requires a database service; explicit `--with=none` is allowed.

Forwarded host ports are controlled by `FORWARD_*_PORT` variables. See [environment variables](environment-variables.md).
