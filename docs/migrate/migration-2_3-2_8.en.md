# Migrating from v2.3 to v2.8

## Introduction
Versions 2.4 to 2.8 of the framework bring many new features: controllers with several routes, file uploads, Twig cache, styled error pages, CSRF protection, running in Docker with FrankenPHP, SQL queries and a code profiler in the debug bar. \
Most of them are **backward compatible**, but a few actions are required on your project. Please read this page carefully before migrating.
Link to the release notes: [v2.4](../changelog.md#v240-08102026), [v2.5](../changelog.md#v250-08102026), [v2.6](../changelog.md#v260-14102026), [v2.7](../changelog.md#v270-17102026), [v2.8](../changelog.md#v280-20102026)

!!! info "The PHP version does not change"

    Like v2.3, v2.8 requires **PHP 8.4** minimum. If your project works with v2.3, your PHP version is fine.

## How to migrate?
To migrate from version 2.3 to version 2.8, follow these steps:

1. **Analyze the changes**: Analyze the changes introduced by the new version to understand their impact on your project.
2. **Back up your data**: Make sure you back up your data before migrating to avoid any loss (see step 3.1 if you use the Docker database).
3. Perform the following actions:

3.1 **Back up the database (Docker services)**:

!!! danger "MySQL is replaced by MariaDB"

    In v2.3, the `database` service in the `compose.yaml` file used the **MySQL 8.0** image. In v2.8, it uses **MariaDB**.
    Both services store their data in the same `var/dbdata` folder, but **MariaDB cannot take over the files of a MySQL 8 database**.
    You must therefore **export your data before changing the `compose.yaml` file**, then re-import it into MariaDB (step 3.6).

If you do not use the Docker services database (SQLite database, or MySQL installed directly on your machine), go to step 3.2.

With the v2.3 services running, export your database to the `var/` folder (not versioned):

```Bash
docker exec edu-web-database mysqldump -uroot -pstudoo --databases app_db > var/backup-app_db.sql
```

!!! note "Adapt the command"

    `edu-web-database` is the container name in v2.3, `studoo` the root password and `app_db` the database name.
    Replace them if you changed them in your `compose.yaml` file.

Check that the file does contain your tables, then stop the v2.3 services:

```Bash
grep -c "CREATE TABLE" var/backup-app_db.sql
docker compose down
```

Finally, set aside the old MySQL data folder (you can delete it once the migration has been validated):

```Bash
mv var/dbdata var/dbdata-mysql8
```

3.2 **Update the framework**:
```Bash
composer update studoo/edu-framework
```

Result:
```bash
[...]
Package operations: 0 installs, 1 update, 0 removals
  - Upgrading studoo/edu-framework (2.3.x-dev xxxxxxx => 2.8.x-dev xxxxxxx): Extracting archive
[...]
```

3.3 **Add the error page templates**:

!!! warning "Required action"

    Since v2.5, error pages use two new templates: `http-500.html.twig` (unexpected error, replaces `http-Default.html.twig`) and `http-403.html.twig` (access forbidden).
    If they are missing from your project, **any error triggers a Twig exception** instead of displaying the error page.

Download the templates into the `app/Template/error/` folder of your project (the `http-404` and `http-405` templates are also styled):

=== ":fontawesome-brands-windows: WINDOWS"

    Open a git bash terminal at the root of your project

    ```bash
    for code in 403 404 405 500; do
      curl --ssl-no-revoke -o app/Template/error/http-$code.html.twig https://raw.githubusercontent.com/studoo-app/edu-framework/2.x/app/Template/error/http-$code.html.twig
    done
    rm app/Template/error/http-Default.html.twig
    ```

=== ":fontawesome-brands-apple: MAC OS"

    Open a terminal at the root of your project

    ```bash
    for code in 403 404 405 500; do
      curl -sS -o app/Template/error/http-$code.html.twig https://raw.githubusercontent.com/studoo-app/edu-framework/2.x/app/Template/error/http-$code.html.twig
    done
    rm app/Template/error/http-Default.html.twig
    ```

!!! tip "Have you customized your error pages?"

    Do not replace your `http-404` and `http-405` templates: download only `http-403` and `http-500`, then carry your customizations of `http-Default.html.twig` over to `http-500.html.twig`.
    The templates receive the `code` variable (HTTP code) and, for the 500 page, `exception_message` (the exception message, displayed only in development mode).

3.4 **Declare the cache folder in the `public/index.php` file**:

Since v2.4, compiled Twig templates are cached. Add the `cache_path` key to the configuration:

```diff
  'base_path'         => __DIR__ . '/../',
  'twig_path'         => __DIR__ . '/../app/Template',
  'route_config_path' => __DIR__ . '/../app/Config/',
++'cache_path'        => __DIR__ . '/../var/cache/',
  'sqlite_path'       => __DIR__ . '/../var/sqlite/',
  'sqlite_logs_path'  => __DIR__ . '/../var/logs/'
```

Without this key, the framework uses the relative path `./var/cache/`, which depends on the folder the server is started from.

3.5 **Update the `.env` file**:

Add the new variables to your `.env` file (the values below are the default values):

```dotenv
## << Code profiler config (debug bar, dev mode only)
EDU_PROFILER=trigger
EDU_PROFILER_KEEP=50
EDU_PROFILER_BUILD=1
## >> Code profiler config

## << Docker config (ports exposed on the host, see compose.yaml)
HTTP_PORT=80
HTTPS_PORT=443
SERVER_NAME=:80
PMA_PORT=8081
DBGATE_PORT=8082
MAILER_SMTP_PORT=1025
MAILER_UI_PORT=8025
## >> Docker config
```

!!! note "The APP_NAME variable"

    Docker container names are now prefixed with the `APP_NAME` variable from the `.env` file (Example: `edu-framework-database` instead of `edu-web-database`).
    Give each project a different name so you can run several of them on the same machine.

Each variable is described in detail on the [The application in Docker](../installation/docker.md#environment-variables) page.

3.6 **Update the Docker files and re-import the database**:

v2.8 adds a **FrankenPHP** web service that runs the application in Docker. It requires three files: `compose.yaml` (updated), `Dockerfile` and `Caddyfile` (new).

=== ":fontawesome-brands-windows: WINDOWS"

    Open a git bash terminal at the root of your project

    ```bash
    for file in compose.yaml Dockerfile Caddyfile; do
      curl --ssl-no-revoke -o $file https://raw.githubusercontent.com/studoo-app/edu-framework/2.x/$file
    done
    ```

=== ":fontawesome-brands-apple: MAC OS"

    Open a terminal at the root of your project

    ```bash
    for file in compose.yaml Dockerfile Caddyfile; do
      curl -sS -o $file https://raw.githubusercontent.com/studoo-app/edu-framework/2.x/$file
    done
    ```

Start the MariaDB database, then re-import the backup from step 3.1 (replace `edu-framework` with the value of `APP_NAME` in your `.env`):

```Bash
docker compose up -d database
docker exec -i edu-framework-database mariadb -uroot -pstudoo < var/backup-app_db.sql
```

!!! bug "Unknown collation: 'utf8mb4_0900_ai_ci'"

    This collation is specific to MySQL 8. If the import fails with this error, replace it in the backup, then run the import again:

    ```Bash
    sed -i.bak 's/utf8mb4_0900_ai_ci/utf8mb4_unicode_ci/g' var/backup-app_db.sql
    ```

Finally, start all the services, including the application (building the image for the first time takes a few minutes):

```Bash
docker compose up -d
```

The application is available at `http://localhost`. For more details, see the [The application in Docker](../installation/docker.md) page.

3.7 **Clear the cache and check the version**:
```Bash
php bin/edu cache:clear
php bin/edu --version
```

Result:
```bash
EduFramework v2.8.x-xxxx
```

## The changes

### **The Docker database switches to MariaDB**
The `database` service uses the `mariadb:latest` image instead of `mysql:8.0` (see step 3.1). The `DB_TYPE=mysql` variable in your `.env` remains valid: MariaDB is compatible with the PDO MySQL driver.

### **Error pages**
The `http-Default.html.twig` template is renamed `http-500.html.twig`, and the new `HttpError403Controller` uses `http-403.html.twig` (see step 3.3).
Any unhandled exception now displays the 500 page, with the exception message **in development mode only** (hidden in production).

### **Request logging**
Requests are now logged by the framework itself, whatever the server (built-in PHP server or FrankenPHP), and no longer by parsing the output of `php bin/edu start`. Consequences:

- **static files** (CSS, images, ...) served directly by the web server no longer appear in the profiler;
- the profiler's pagination bar is replaced by the "Results" selector and the "Last 10" / "Last" shortcuts.

The logs database (`var/logs/core_logs.sqlite`) is **upgraded automatically**: no action is needed.

### **Database connection in dev mode**
In development mode, `DatabaseService::getConnect()` returns a `TraceablePDO` connection, which **extends `PDO`** and measures each SQL query. Your application code does not need to change, unless it compares the exact class of the connection (`get_class($pdo) === PDO::class`): use `$pdo instanceof PDO` instead. In production, the connection remains a native `PDO`.

### **Dependencies**
The `zircote/swagger-php` dependency moves to version **5.8**: the `vendor/bin/openapi` command now works without errors under PHP 8.4 and 8.5. The `OpenApi\Attributes` attributes remain the same, no action is needed.

## What's new

### **One controller, several routes** (v2.4)
The `controller` key of the `app/Config/routes.yaml` file accepts the `Controller\VilleController::index` syntax to call a specific method. Without a method, `execute()` is called as before.

For more details, see the [One controller, several routes](../build/controller-edu.md#one-controller-several-routes-several-methods) section.

### **File uploads** (v2.4)
The `Request` object handles files sent by a form: `hasFile()`, `getFile()`, `getFiles()`, then `isValid()`, `getExtension()` and `move()` on each file.

For more details, see the [The HTTP request](../boost/resquet.md) page.

### **Twig cache and the cache:clear command** (v2.4)
Compiled templates are stored in `var/cache/twig` and automatically recompiled when they are modified. The `php bin/edu cache:clear` command deletes the application cache.

### **The make:entity command** (v2.5)
The `php bin/edu make:entity` command generates an entity (`app/Entity`) and its repository (`app/Repository`), interactively or with the `--fields` option.

For more details, see the [Edu commands](../installation/command-edu.md) page.

### **CSRF protection** (v2.6)
The new `CsrfService` service protects your forms against CSRF attacks: the Twig function `csrf_token('nom_route')` in the form, and `Request::isCsrfTokenValid('nom_route')` in the controller.

For more details, see the [CSRF protection](../build/csrf.md) page.

### **The application in Docker with FrankenPHP** (v2.7)
The `frankenphp` service runs the application in Docker, over HTTP or local HTTPS. The classic startup with `php bin/edu start` is still available.

For more details, see the [The application in Docker](../installation/docker.md) page.

### **The debug bar and the profiler** (v2.7 and v2.8)
The debug bar and the profiler get richer:

- **request description** and **error message** logged with the request (v2.7);
- **SQL queries** of each HTTP request: SQL block in the bar and SQL view in the profiler (v2.7);
- **development services** (Mailpit, DbGate, phpMyAdmin, ...) listed with their URLs in the DEV block (v2.7);
- **code profiler**: time spent in each PHP function, flamegraph export (v2.8).

For more details, see the [The debug bar and the profiler](../boost/debug-bar.md) page.
