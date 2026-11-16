# Migrate from version v2.15 to v2.16

## Introduction
Version 2.16 of the framework brings the **application cache** (`CacheService`): caching data coming from the database, in files or in a [Redis](https://redis.io){:target="_blank"} server.

It is **backward compatible**: your application works after updating the framework, **without changing your code or your Docker files**. Without any configuration, the cache uses files (`CACHE_DRIVER=file`) and no connection to Redis is opened. Step 3.3 is **optional**: it adds Redis to your Docker environment, to follow the [Caching data with Redis](../build/cache-redis.md) tutorial.

Release notes: [v2.16](../changelog.md#v2160-16112026)

!!! info "The PHP version does not change"

    Like v2.15, v2.16 requires **PHP 8.4** minimum. No new PHP extension is required: the Redis client ([Predis](https://github.com/predis/predis){:target="_blank"}) is written in PHP and installed by Composer.

## How to migrate?
To migrate from version 2.15 to version 2.16, follow these steps:

1. **Review the changes**: read the [Changes](#changes) section to check that none of them affects your project.
2. **Version your work**: make a commit before you start, so that you can go back (`git status` must show nothing). The database is not modified by this migration.
3. Perform the following actions:

3.1 **Update the framework**:
```Bash
composer update studoo/edu-framework
```

Result:
```bash
[...]
Package operations: 1 install, 1 update, 0 removals
  - Installing predis/predis (v3.x.x): Extracting archive
  - Upgrading studoo/edu-framework (dev-main xxxxxxx => dev-main yyyyyyy): Extracting archive
[...]
```

The new `predis/predis` dependency (the Redis client) is installed automatically.

3.2 **Check the version and the cache**:
```Bash
php bin/edu check:config
```

The new **Cache check** section shows the cache driver:
```text
Cache check :
  [*] Driver du cache : file (variable CACHE_DRIVER)
```

Version `v2.16.0` is shown in the debug bar (dev mode) and by the `php bin/edu` command.

The migration is complete: the following steps are optional.

3.3 **(Optional) Add Redis to Docker**:

In the `compose.yaml` file, add the following two services, at the same level as the `database` service:

```yaml
  redis:
    container_name: ${APP_NAME}-redis
    # Redis server: application cache (CacheService) - https://redis.io
    image: redis:8-alpine
    ports:
      - "${REDIS_PORT:-6379}:6379"
    environment:
      REDIS_PASSWORD: ${REDIS_PASSWORD:-}
    # empty requirepass (default) => no password
    command: ["redis-server", "--appendonly", "yes", "--requirepass", "${REDIS_PASSWORD:-}"]
    volumes:
      - ./var/redis:/data
    healthcheck:
      test: ["CMD-SHELL", "redis-cli --no-auth-warning $${REDIS_PASSWORD:+-a \"$$REDIS_PASSWORD\"} ping | grep -q PONG"]
      interval: 5s
      timeout: 3s
      retries: 5
    restart: always

  redisinsight:
    container_name: ${APP_NAME}-redisinsight
    # Web interface to explore the Redis keys: http://localhost:5540
    image: redis/redisinsight:latest
    ports:
      - "${REDIS_UI_PORT:-5540}:5540"
    environment:
      RI_REDIS_HOST: redis
      RI_REDIS_PORT: 6379
      RI_REDIS_ALIAS: ${APP_NAME}
      RI_REDIS_DB: ${REDIS_DB:-0}
      RI_REDIS_PASSWORD: ${REDIS_PASSWORD:-}
    volumes:
      - redisinsight_data:/data
    depends_on:
      redis:
        condition: service_healthy
    restart: always
```

In the `frankenphp` service, add the cache variables to the `environment:` list: inside the Docker network, the Redis server is reached by the service name (`redis`), not by `127.0.0.1`.

```yaml
      CACHE_DRIVER: ${CACHE_DRIVER:-file}
      CACHE_PREFIX: ${CACHE_PREFIX:-edu:}
      CACHE_TTL: ${CACHE_TTL:-3600}
      REDIS_HOST: redis
      REDIS_PORT: 6379
      REDIS_PASSWORD: ${REDIS_PASSWORD:-}
      REDIS_DB: ${REDIS_DB:-0}
      REDIS_UI_PORT: ${REDIS_UI_PORT:-5540}
```

Finally, declare the Redis Insight volume in the `volumes:` list at the end of the file:

```diff
 volumes:
   caddy_data:
   caddy_config:
++  redisinsight_data:
```

!!! tip "Do not add a dependency on Redis"

    Do not add `redis` to the `depends_on:` of the `frankenphp` service: the application must be able to start even if Redis is stopped, or if port `6379` is already used by another project on your machine (in that case, change `REDIS_PORT` in the `.env` file).

3.4 **(Optional) Enable Redis in the `.env` file**:

```diff
++ ## << Config Cache
++ CACHE_DRIVER=redis
++ CACHE_PREFIX=edu:
++ CACHE_TTL=3600
++ REDIS_HOST=127.0.0.1
++ REDIS_PORT=6379
++ REDIS_PASSWORD=
++ REDIS_DB=0
++ REDIS_UI_PORT=5540
++ ## >> Config Cache
```

Then start the services and check the connection:

```Bash
docker compose up -d redis redisinsight
php bin/edu check:config
```

```text
Cache check :
  [*] Driver du cache : redis (variable CACHE_DRIVER)
  [*] Serveur Redis 127.0.0.1:6379 joignable (version 8.x)
```

3.5 **Commit the migration**:
```Bash
git add composer.json composer.lock compose.yaml
git commit -m "chore: Edu Framework v2.16 migration"
```

Do not add the `.env` file: it contains your local settings and is never versioned. If your project has a `.env.example` file, you can copy the "Config Cache" section of step 3.4 into it for your teammates.

## Changes

### **The cache:clear command**

`php bin/edu cache:clear` still clears the `var/cache` folder (TWIG cache...), and **also clears the application cache**. Two options let you choose:

| Command | What is cleared |
|---------|-----------------|
| `php bin/edu cache:clear` | The `var/cache` folder and the application cache |
| `php bin/edu cache:clear --files-only` | Only the `var/cache` folder (v2.15 behaviour) |
| `php bin/edu cache:clear --app-only` | Only the application cache (for example the `edu:*` keys in Redis) |

With Redis, only the keys with the `CACHE_PREFIX` prefix are deleted: a shared Redis server is never fully cleared. If Redis is stopped, the command shows a warning but does not fail.

### **New environment variables**

v2.16 reads the `CACHE_DRIVER`, `CACHE_PREFIX`, `CACHE_TTL`, `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`, `REDIS_DB` and `REDIS_URL` variables. They are all **optional**.

!!! note "Does your project already use these names?"

    - A `REDIS_URL` variable in the `redis://...` or `rediss://...` format is read by the framework to deduce the missing `REDIS_*` variables (a variable that is already defined is never overwritten). A `REDIS_URL` in another format (for example `tcp://host:6379`) is **ignored**.
    - A malformed `REDIS_URL`, or an invalid value of `CACHE_DRIVER` or `CACHE_TTL`, does not prevent the application from starting: the error is only reported when the cache is used, and by the `php bin/edu check:config` command.

### **The debug bar**

The debug bar shows a new **Cache** block when the page uses the cache (reads found and missing, writes, time). The panel of the **DEV** block shows the cache driver and, with Redis, the link to Redis Insight.

## New features

### **The application cache** (v2.16)

| Feature | What you learn |
|---------|----------------|
| `CacheService::getCache()` (`get`, `set`, `has`, `delete`, `clear`, `remember`) | Cache data, choose a lifetime |
| `redis`, `file` and `none` drivers (`CACHE_DRIVER`) | Switch storage through configuration, without changing the code |
| `CacheService::getRedis()` | Use Redis commands (`RENAME`, hashes...) |
| `redis` and `redisinsight` Docker services | Explore the keys of a Redis cache |

To discover the cache step by step: [Caching data with Redis](../build/cache-redis.md).
