# Customize the runtime

The default runtime lives in the installed Composer package. Publish it before editing Dockerfiles or runtime configuration:

```bash
vendor/bin/corsair publish
```

Corsair copies runtime and database files into `./docker` and updates `compose.yaml` to build from that directory. Commit the published files so the team shares the same environment.

Edit the relevant files under `docker/<php-version>/`, then rebuild:

```bash
vendor/bin/corsair build
vendor/bin/corsair up -d
```

Use this workflow for PHP extensions, `php.ini`, PHP-FPM, Nginx, mail configuration, Supervisor, and startup behavior.

