# How to enable Xdebug

Every runtime image ships an Xdebug configuration (`conf/php-xdebug.ini`) that's disabled by default. Two environment variables in `.env` control it:

```ini
XDEBUG_MODE=debug
XDEBUG_CONFIG=client_host=host.docker.internal
```

`XDEBUG_MODE` is substituted directly into `xdebug.mode`; leave it at the default `off` for normal use, since Xdebug adds overhead even when idle. Common values when debugging are `debug` (step debugging) and `develop,debug` (better stack traces plus step debugging). `XDEBUG_CONFIG` defaults to `client_host=host.docker.internal`, which already tells Xdebug to connect back to your host machine's IDE listening on port `9003` — you shouldn't need to change it on Docker Desktop or recent Docker Engine with `extra_hosts: host.docker.internal:host-gateway` (which Corsair's generated `compose.yaml` sets for the app service).

After changing `.env`, recreate the app container so it picks up the new environment:

```bash
vendor/bin/corsair up -d --force-recreate magento.test
```

Replace `magento.test` with your app service's name (`maho.test`, `openmage.test`, `mageos.test`) if different.

Point your IDE's PHP debug listener at port `9003` and start listening before triggering the request. Profiling output (when `XDEBUG_MODE` includes `profile`) is written to `var/profile` inside the container, which maps to your project's own `var/profile` directory.
