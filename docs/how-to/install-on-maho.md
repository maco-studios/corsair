# How to install Corsair on a MahoCommerce project

Maho 26.11 requires PHP 8.5 or later; Maho 26.9 is the last release that runs on PHP 8.3 or 8.4. Corsair selects PHP 8.5 unless you pass `--php`. The web server uses `public/` as the document root, and only `index.php`, `rest.php`, and `api.php` are executed through PHP.

From an empty directory:

```bash
composer create-project mahocommerce/maho-starter .
composer require maco-studios/corsair --dev
vendor/bin/corsair install --platform=maho
vendor/bin/corsair up -d
```

Corsair detects `maho` automatically from the `mahocommerce/*` package in `composer.json` when `--platform` is omitted. Installing with a PHP version earlier than `8.3` fails outright; installing with `8.3` or `8.4` succeeds but warns that only Maho 26.9 will run.

Corsair doesn't wrap Maho's own application installer — install it yourself the same way you'd run `bin/magento setup:install` for Magento, using the database credentials `install` wrote to `.env`:

```bash
vendor/bin/corsair maho install \
  --license_agreement_accepted=yes \
  --db_host=mysql --db_name=maho --db_user=corsair --db_pass=password \
  --url=http://localhost/ --secure_base_url=http://localhost/ \
  --use_secure=0 --use_secure_admin=0 \
  --admin_firstname=Store --admin_lastname=Admin \
  --admin_email=admin@example.com --admin_username=admin --admin_password=<password> \
  --sample_data=1
vendor/bin/corsair maho index:reindex:all
vendor/bin/corsair maho cache:flush
```

`DB_HOST` is `mysql` unless you installed `mariadb` instead; `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` default to `maho`, `corsair`, and `password` respectively. Drop `--sample_data=1` if you don't want sample data, and see `vendor/bin/corsair maho install --help` for every option Maho's installer accepts.

Run any other Maho command the same way, e.g. `vendor/bin/corsair maho cron:list`.

The scheduler container runs `./maho cron:run default` and `./maho cron:run always` every minute, and the queue container runs `./maho queue:work --exclusive` — that's what scheduled jobs and resized images need. Both wait until `./maho` exists before doing anything.

The installer leaves cache and sessions on the filesystem. If you installed the `redis` service, it already runs with `maxmemory-policy volatile-lfu`, which Maho's Redis cache backend requires — see [Enable Redis cache and sessions](enable-redis-cache-and-sessions.md) to switch Maho over to it.

Add Mailpit when you want to catch outgoing mail: `vendor/bin/corsair add mailpit`.
