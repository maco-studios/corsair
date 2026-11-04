# Getting started: your first Corsair environment

This tutorial walks through setting up a brand-new MahoCommerce store with Corsair, from an empty directory to a working admin login, so you can see the whole workflow end to end. It assumes Docker Engine (with Compose) or Docker Desktop, Git, and PHP's Composer are already installed on your machine.

MahoCommerce is used here because it has the simplest installer of the four supported platforms. Corsair's job stops at the Docker environment for every platform, Maho included — it never runs the application's own installer for you, the same way it doesn't run `bin/magento setup:install` for Magento. Once you've done this tutorial, the [how-to guides](../how-to/) cover each platform, including those.

## 1. Create the project

```bash
mkdir my-store && cd my-store
composer create-project mahocommerce/maho-starter .
```

This downloads a fresh Maho application into the current directory.

## 2. Add Corsair

```bash
composer require maco-studios/corsair --dev
```

Corsair is installed as a dev dependency, so it never ships to production.

## 3. Install the Corsair scaffolding

```bash
vendor/bin/corsair install --platform=maho
```

In an interactive terminal, you'll be prompted for which services to install — accept the defaults (`mysql`, `redis`) by pressing Enter. This writes `compose.yaml` and `.env` in your project, and builds the application image.

You should see a confirmation that scaffolding was installed, along with a reminder that Maho requires PHP 8.5 or later (Corsair selects it by default) and a note about the database credentials written to `.env`.

## 4. Start the containers

```bash
vendor/bin/corsair up -d
```

`vendor/bin/corsair` aliases `docker compose` for any command it doesn't recognize as its own, the same way `vendor/bin/sail` does for Laravel — so `up`, `down`, `ps`, and `logs` all work without typing `docker compose` at all.

Check that everything is running:

```bash
vendor/bin/corsair ps
```

You should see `maho.test` (the app), `scheduler`, `queue`, `mysql`, and `redis` all `Up`.

## 5. Install Maho

Corsair's job is the Docker environment — installing the application itself is up to you, the same way you'd run `bin/magento setup:install` for Magento. Run Maho's own installer through the `maho` shortcut, using the database credentials `install` already wrote to `.env`:

```bash
vendor/bin/corsair maho install \
  --license_agreement_accepted=yes \
  --db_host=mysql --db_name=maho --db_user=corsair --db_pass=password \
  --url=http://localhost/ --secure_base_url=http://localhost/ \
  --use_secure=0 --use_secure_admin=0 \
  --admin_firstname=Store --admin_lastname=Admin \
  --admin_email=admin@example.com --admin_username=admin --admin_password=corsair-password1 \
  --sample_data=1
vendor/bin/corsair maho index:reindex:all
vendor/bin/corsair maho cache:flush
```

This runs `./maho install` inside the `maho.test` container, then reindexes and flushes the cache so the storefront reflects it immediately.

## 6. Log in

Open `http://localhost/admin` in your browser. Sign in with:

- **Username:** `admin`
- **Password:** `corsair-password1`

You should land on the Maho admin dashboard, with sample data loaded in the catalog.

## What you've done

You now have an isolated, disposable Maho environment: its own containers, its own database, its own network — nothing installed on your host machine beyond Docker and Composer. Bring it down with `vendor/bin/corsair down`, or `vendor/bin/corsair down -v` to also drop the database volume and start over.

## Next steps

- [Reference: every CLI command and option](../reference/cli-commands.md)
- [How to move Maho's cache and sessions to Redis](../how-to/enable-redis-cache-and-sessions.md)
- [How to add a service](../how-to/add-a-service.md), like Mailpit to catch outgoing mail
- [Why cron and queues run in their own containers](../explanation/scheduler-and-queue-containers.md)
