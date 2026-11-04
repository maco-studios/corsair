# How to install Corsair on a MahoCommerce project

Maho 26.11 requires PHP 8.5 or later; Maho 26.9 is the last release that runs on PHP 8.3 or 8.4. Corsair selects PHP 8.5 unless you pass `--php`. The web server uses `public/` as the document root, and only `index.php`, `rest.php`, and `api.php` are executed through PHP.

From an empty directory:

```bash
composer create-project mahocommerce/maho-starter .
composer require maco-studios/corsair --dev
vendor/bin/corsair corsair:install --platform=maho
vendor/bin/corsair up -d
vendor/bin/corsair maho:install --sample-data
```

Corsair detects `maho` automatically from the `mahocommerce/*` package in `composer.json` when `--platform` is omitted. Installing with a PHP version earlier than `8.3` fails outright; installing with `8.3` or `8.4` succeeds but warns that only Maho 26.9 will run.

`maho:install` reads `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` from `.env` and runs `./maho install` inside `maho.test`, then reindexes and flushes the cache unless `--skip-reindex` is passed. The default admin credentials are `admin` / `corsair-password1` — see the [CLI reference](../reference/cli-commands.md#maho-install) to change them.

Run any other Maho command the same way:

```bash
vendor/bin/corsair maho cache:flush
vendor/bin/corsair maho index:reindex:all
vendor/bin/corsair maho cron:list
```

The scheduler container runs `./maho cron:run default` and `./maho cron:run always` every minute, and the queue container runs `./maho queue:work --exclusive` — that's what scheduled jobs and resized images need. Both wait until `./maho` exists before doing anything.

The installer leaves cache and sessions on the filesystem. If you installed the `redis` service, it already runs with `maxmemory-policy volatile-lfu`, which Maho's Redis cache backend requires — see [Enable Redis cache and sessions](enable-redis-cache-and-sessions.md) to switch Maho over to it.

Add Mailpit when you want to catch outgoing mail: `vendor/bin/corsair corsair:add mailpit`.
