# Environment variables

Corsair writes these values to `.env` when the corresponding platform or service is selected.

| Variable | Meaning |
| --- | --- |
| `CORSAIR_PLATFORM` | Resolved platform identifier |
| `DB_HOST`, `DB_PORT` | Database service hostname and port |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Local database credentials |
| `REDIS_HOST`, `REDIS_PORT` | Redis service connection |
| `ELASTICSEARCH_HOST`, `ELASTICSEARCH_PORT` | Elasticsearch connection |
| `OPENSEARCH_HOST`, `OPENSEARCH_PORT` | OpenSearch connection |
| `RABBITMQ_HOST`, `RABBITMQ_PORT`, `RABBITMQ_USER`, `RABBITMQ_PASSWORD`, `RABBITMQ_VHOST` | RabbitMQ connection |
| `MAIL_HOST`, `MAIL_PORT` | Mailpit SMTP connection |
| `VARNISH_HOST`, `VARNISH_PORT` | Varnish connection |

These values are read by the generated Compose file:

| Variable | Purpose |
| --- | --- |
| `APP_PORT` | Host port mapped to the application HTTP port |
| `WWWUSER`, `WWWGROUP` | Runtime UID and GID |
| `XDEBUG_MODE`, `XDEBUG_CONFIG` | Xdebug configuration |
| `FORWARD_*_PORT` | Host port overrides for services |

Corsair updates keys it owns when running `install` or `add`; application-specific keys are not managed by Corsair.
