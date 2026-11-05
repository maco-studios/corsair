# Enable Xdebug

Corsair ships Xdebug in the runtime image but leaves it disabled by default.

Add these values to `.env`:

```dotenv
XDEBUG_MODE=debug,develop
XDEBUG_CONFIG=client_host=host.docker.internal
```

Recreate the application service:

```bash
vendor/bin/corsair up -d --force-recreate magento.test
```

Use `maho.test`, `openmage.test`, or `mageos.test` for the selected platform. Configure the IDE to listen on port `9003`, then trigger a request.

For profiling, include `profile` in `XDEBUG_MODE`; output is written to the project's `var/profile` directory.
