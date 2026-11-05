# Services, cron, and queues

Corsair keeps databases, caches, search engines, queues, mail, and HTTP caching in separate containers so each service can be inspected, restarted, and persisted independently.

Scheduler containers run the platform's cron command on a loop. Magento 2 and Mage-OS use `bin/magento cron:run`; Maho runs its two cron modes; OpenMage runs `cron.php`.

Queue containers are created for platforms with queue consumers. Magento 2 and Mage-OS start the consumers reported by `bin/magento queue:consumers:list`; Maho runs its queue worker. OpenMage has no Corsair queue worker.

This separation keeps long-running workers out of the web request container and makes their logs independently visible through Docker Compose.

