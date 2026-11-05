# Configure Maho's cache and sessions with Redis

Corsair can provide the Redis container, but it does not rewrite Maho's application configuration. Follow [MahoCommerce's official Redis documentation](https://mahocommerce.com/hosting/redis/) for the complete set of backend options.

## 1. Add Redis

Redis is selected by default for Maho. Add it to an existing installation if it is missing:

```bash
vendor/bin/corsair add redis
vendor/bin/corsair up -d
```

## 2. Configure Maho

Use `redis` as the hostname from inside the application container and port `6379`. Configure Maho's cache and session backends according to the official documentation.

Do not use `127.0.0.1` as the Redis host: that address points to the Maho container itself.

## 3. Verify the connection

```bash
vendor/bin/corsair exec redis redis-cli ping
vendor/bin/corsair maho cache:flush
```

The Redis data volume is named `corsair-redis` and persists across container recreation.

