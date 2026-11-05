# Troubleshoot Corsair

## The command says no Compose file exists

Run installation from the project root:

```bash
vendor/bin/corsair install
```

## A service does not start

Inspect status and logs:

```bash
vendor/bin/corsair ps
vendor/bin/corsair logs service-name
```

Check for a host-port collision in `.env`, then change the matching `FORWARD_*_PORT` value and recreate the service.

## The platform was detected incorrectly

Override detection explicitly:

```bash
vendor/bin/corsair install --platform=mageos
```

Corsair checks an existing Compose file, then Composer package names, then prompts interactively. See [Platform detection](../explanation/platform-detection.md).

## Composer reports an incompatible PHP platform

Inspect the selected runtime and project constraints:

```bash
vendor/bin/corsair install --php=8.3
vendor/bin/corsair composer check-platform-reqs
```

## The application cannot connect to MySQL or Redis

Use the Compose service hostname from inside the app container (`mysql`, `mariadb`, or `redis`). `127.0.0.1` points back to the application container itself.

## OpenMage installation fails early

Check the OpenMage release and its official installation guidance first. Then inspect application logs and the Nginx configuration generated for `openmage.test`.

