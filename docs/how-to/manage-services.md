# Manage services

Add supported services to an existing installation:

```bash
vendor/bin/corsair add mailpit,varnish
vendor/bin/corsair up -d
```

The command appends services to the application `depends_on` list, adds their environment values, and updates scheduler or queue dependencies where applicable.

Use `vendor/bin/corsair add` without an argument for the interactive selector. Use `vendor/bin/corsair add none` to make no changes.

Inspect services and logs:

```bash
vendor/bin/corsair ps
vendor/bin/corsair logs -f redis
```

## Change forwarded host ports

Set the relevant value in `.env`, then recreate the affected service:

```dotenv
FORWARD_REDIS_PORT=6380
FORWARD_MAILPIT_DASHBOARD_PORT=8026
```

```bash
vendor/bin/corsair up -d --force-recreate redis mailpit
```

See [Services reference](../reference/services.md) for all available services.

