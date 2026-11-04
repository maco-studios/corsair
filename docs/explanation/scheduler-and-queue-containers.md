# Why cron and queues run in their own containers

When Corsair builds `compose.yaml`, it adds one or two extra services alongside the app container: `scheduler` always, and `queue` for every platform except OpenMage (which has no message queue to run). Both are copies of the app service's image and environment, minus the exposed port, running a `while true` loop instead of PHP-FPM and Nginx:

- `magento`/`mageos`: `scheduler` runs `bin/magento cron:run` every 60 seconds; `queue` starts every registered consumer (`bin/magento queue:consumers:list`) every 60 seconds.
- `maho`: `scheduler` runs `./maho cron:run default` then `./maho cron:run always` every 60 seconds; `queue` runs `./maho queue:work --exclusive` every 5 seconds.
- `openmage`: `scheduler` runs `./cron.php` every 60 seconds; there is no `queue` service.

The app container's own startup script (`start-container`) only ever launches `supervisord` running `php-fpm` and `nginx` — it does not run cron or a queue worker itself, by design.

## Why not just cron inside the app container

A single container running a web server, PHP-FPM, cron, and a queue worker all under one `supervisord` makes a few things harder than they need to be:

- **Restarting the web server restarts the scheduler, and vice versa.** If a long-running queue consumer hangs or a cron job needs a restart, you don't want to take the storefront down to fix it, or take the storefront down and silently lose cron in the process.
- **Resource limits blur together.** A runaway consumer competing with PHP-FPM for the same container's CPU/memory budget degrades the storefront instead of being something you can see and throttle independently.
- **Logs and health checks become ambiguous.** `docker compose ps` and `docker compose logs` work per-service; folding scheduler, queue, and web server into one process group means you lose that separation exactly where it's most useful — figuring out why reindexing is stuck versus why checkout is slow.

Giving the scheduler and queue their own containers means `docker compose restart scheduler` or `docker compose logs -f queue` do what they say, and a problem in one doesn't take the others down with it — the same reasoning that leads Docker Compose examples in general toward one process per container rather than one process group per container.
