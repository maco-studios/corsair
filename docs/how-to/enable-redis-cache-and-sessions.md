# How to move Maho's cache and sessions to Redis

By default, `maho:install` leaves cache and sessions on the filesystem. If you installed the `redis` service (`corsair:install --with=redis,...` or `corsair:add redis`), it already runs with `redis-server --maxmemory-policy volatile-lfu`, which is what Maho's Redis cache backend requires.

Add this inside `<global>` in `app/etc/local.xml`:

```xml
<cache>
    <backend>redis</backend>
    <backend_options>
        <dsn>redis://redis/0</dsn>
    </backend_options>
</cache>
<session_save>redis</session_save>
<redis_session>
    <dsn>redis://redis/1</dsn>
</redis_session>
```

`redis` here is the Compose service hostname Corsair wrote to `REDIS_HOST` in `.env` — see [Services](../reference/services.md). Cache uses database `0` and sessions use database `1` on the same Redis instance; adjust the DSNs if you need them on separate instances.

See [MahoCommerce's Redis documentation](https://mahocommerce.com/hosting/redis/) for the full set of backend options.
