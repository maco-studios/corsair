# How to publish and customize the Docker files

By default, `compose.yaml` builds the app image from the files inside `vendor/maco-studios/corsair/resources/runtimes/<php-version>` — the Dockerfile, `start-container`, `supervisord.conf`, and the `conf/` directory (php.ini, php-fpm, nginx, msmtp). You can't edit those in place, since they live in `vendor/`.

To customize them — a different `php.ini` setting, an extra PHP extension, a tweaked Nginx rule — publish them into your own project first:

```bash
vendor/bin/corsair publish
```

This copies `resources/runtimes` and `resources/database` from the package into `./docker` in your project, and rewrites `compose.yaml`'s `build.context` paths (and the database init-script volume mounts) to point at `./docker/...` instead of `./vendor/maco-studios/corsair/resources/...`.

`publish` requires `compose.yaml` to already exist — run `install` first. After publishing, edit the files under `./docker` directly and rebuild:

```bash
vendor/bin/corsair build
vendor/bin/corsair up -d
```

Because `./docker` is now part of your project rather than `vendor/`, commit it to version control so teammates get the same customized image.
