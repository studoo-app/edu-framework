# Deploying your application

Your application runs on your computer: it is time to put it online, accessible to everyone over HTTPS with your own domain name.

To do this, Edu-Framework relies on [FrankenDeploy](https://yoanbernabeu.github.io/frankendeploy/){:target="_blank"}, a command-line tool that deploys a [FrankenPHP](https://frankenphp.dev){:target="_blank"} application to a **Debian** or **Ubuntu** server (VPS): it installs Docker on the server, obtains the HTTPS certificate (Let's Encrypt), creates the database and updates the application with zero downtime.

!!! tip "Without Docker?"

    If you do not want to use Docker, see [Deploying with Deployer (without Docker)](deployer.md): PHP and Caddy are installed directly on the server.

!!! warning "Experimental tool"

    FrankenDeploy is young and was originally designed for Symfony: Edu-Framework provides the `Dockerfile` and the configuration that make it compatible. See the [FrankenDeploy documentation](https://yoanbernabeu.github.io/frankendeploy/){:target="_blank"} for details on each command.

## How does it work?

```mermaid
flowchart LR
    P["Your computer<br/>frankendeploy deploy"] -->|"SSH"| S
    subgraph S["Debian server (VPS)"]
        C["Caddy<br/>HTTPS :443"] -->|":8080"| A["Application container<br/>FrankenPHP + PHP 8.4"]
        A -->|"DATABASE_URL"| DB[("MariaDB")]
        A -.-> SH["Shared folders<br/>.env.local, var/logs, var/sessions,<br/>var/sqlite, public/upload"]
    end
    N["Browser"] -->|"https://mon-app.fr"| C
```

1. On your computer, FrankenDeploy builds the **production Docker image** from the project's `Dockerfile` (`frankenphp_prod` stage)
2. The image is sent to the server and started **alongside** the old version
3. When the new version responds correctly (*healthcheck*), traffic switches over to it and the old one is stopped
4. If it fails, the old version keeps serving: your users notice nothing

## Before you start

- [ ] A **server** (VPS) running Debian 12+ or Ubuntu 22.04+, with 2 GB of RAM
- [ ] **Key-based SSH** access to this server, with the `root` user or a `sudo` user without a password
- [ ] A **domain name** whose DNS `A` record points to the server's IP address
- [ ] **Docker Desktop** installed on your computer
- [ ] The **FrankenDeploy CLI** installed on your computer:

=== "macOS"

    ```bash
    brew install yoanbernabeu/tap/frankendeploy
    ```

=== "Linux"

    ```bash
    curl -fsSL https://raw.githubusercontent.com/yoanbernabeu/frankendeploy/main/scripts/install.sh | sh
    ```

Check the installation:

```bash
frankendeploy --version
```

!!! warning "Project created before version 2.9.0"

    The production image relies on three files at the root of the project: the `Dockerfile` (with the `frankenphp_prod` stage), `docker-entrypoint.sh` and `.dockerignore`. If your project was created before version 2.9.0, copy them from the framework installed by Composer:

    ```bash
    cp vendor/studoo/edu-framework/Dockerfile vendor/studoo/edu-framework/docker-entrypoint.sh vendor/studoo/edu-framework/.dockerignore .
    ```

    Also check that the `composer.lock` file is under version control (it must not be listed in `.gitignore`): the production image installs exactly the versions it contains.

## Step 1: describe the deployment

The `make:deploy` command generates the `frankendeploy.yaml` file at the root of the project:

```bash
php bin/edu make:deploy --domain=mon-app.fr
```

| Option         | Description                                                    | Default             |
|----------------|----------------------------------------------------------------|---------------------|
| `--domain`     | domain name of the application                                 | none                |
| `--name`       | name of the application on the server (lowercase, hyphens), unique on the server | folder name (mandatory inside the Docker container) |
| `--database`   | database: `mariadb`, `mysql`, `sqlite` or `none`       | `mariadb`           |
| `--force`      | overwrites an existing `frankendeploy.yaml` file                |                     |

!!! info "Inside the development Docker container"

    In the [100% Docker workflow](../installation/docker.md), the project is mounted in the container's `/app` folder: specify the application name, otherwise every project would be called `app` on the server.

    ```bash
    docker compose exec frankenphp php bin/edu make:deploy --name=mon-app --domain=mon-app.fr
    ```

The generated file looks like this:

```yaml
name: mon-app

php:
  version: "8.4"
  extensions: [pdo_sqlite, pdo_mysql, mbstring, zip, intl, opcache]

# Database managed by FrankenDeploy (container on the server)
database:
  driver: mariadb
  version: "11"
  managed: true

deploy:
  domain: mon-app.fr
  healthcheck_path: /
  keep_releases: 5
  shared_files:
    - .env.local
  shared_dirs:
    - var/logs
    - var/sessions
    - var/sqlite
    - public/upload
```

!!! info "The healthcheck"

    Before switching traffic, FrankenDeploy checks that the `healthcheck_path` page (`/` by default) responds with an HTTP 200 code. Docker also monitors the application with the `HEALTHCHECK` line of the `Dockerfile`, used in particular by `frankendeploy env set ... --reload`.

    If your home page requires users to be logged in, choose another public route and change **both**: `healthcheck_path` in `frankendeploy.yaml` and the URL in the `HEALTHCHECK` line of the `Dockerfile` (`http://localhost:8080/...`).

Commit this file with your project:

```bash
git add frankendeploy.yaml
git commit -m "Deployment configuration"
```

## Step 2: prepare the server

These commands only need to be run **once** per server. First, register the server under the name `prod`:

```bash
frankendeploy server add prod deploy@mon-serveur.fr
```

Then prepare it (the email address is used for the Let's Encrypt certificate):

```bash
frankendeploy server setup prod --email moi@exemple.fr
```

This command installs Docker, configures the firewall (SSH, HTTP, HTTPS), protects the server against SSH attacks (Fail2ban) and starts the Caddy proxy that handles HTTPS.

## Step 3: configure the environment variables

In production, the `.env` file from your computer **is not sent** to the server (it is excluded from the Docker image). The configuration is stored on the server, in a `.env.local` file specific to the application:

```bash
frankendeploy env set prod APP_ENV=prod
```

FrankenDeploy also requires the `APP_SECRET` variable (inherited from Symfony): during the first deployment, it offers to generate it for you.

For a password or an API key, use masked input:

```bash
frankendeploy env set prod MAILER_DSN --from-stdin
```

`env set` accepts **only one variable per command**. To define several, repeat the command or send a file:

```bash
frankendeploy env push prod .env.prod
```

List the defined variables:

```bash
frankendeploy env list prod
```

!!! danger "The # character in a value"

    FrankenDeploy writes the values without quotes in the `.env.local`: when it is read, everything after a `#` is ignored (`S3cr#t` becomes `S3cr`). Avoid `#` in passwords. In a URL (`DATABASE_URL`, `MAILER_DSN`), encode the special characters of the user and the password: `#` => `%23`, `@` => `%40`, `/` => `%2F`, `?` => `%3F`.

    A malformed `DATABASE_URL` stops the application with the message « La variable DATABASE_URL est invalide » (the DATABASE_URL variable is invalid).

!!! info "The database is configured automatically"

    With a managed database (`managed: true`), FrankenDeploy creates the MariaDB container and passes the `DATABASE_URL` variable to the application:

    ```
    mysql://mon-app:motdepasse@mon-app-db:3306/mon_app
    ```

    Edu-Framework automatically derives the `DB_HOST_STATUS`, `DB_TYPE`, `DB_HOST`, `DB_SOCKET`, `DB_USER`, `DB_PASSWORD` and `DB_NAME` variables from it. A `DB_*` variable defined in the `.env.local` still takes precedence.

    This `DATABASE_URL` is not stored in the `.env.local`: `frankendeploy env list prod` does not show it. To view it:

    ```bash
    frankendeploy exec prod printenv DATABASE_URL
    ```

!!! info "With an SQLite database (`--database=sqlite`)"

    No `DATABASE_URL` is passed: define the variables one by one.

    ```bash
    frankendeploy env set prod DB_HOST_STATUS=true
    ```

    ```bash
    frankendeploy env set prod DB_TYPE=sqlite
    ```

    ```bash
    frankendeploy env set prod DB_NAME=app_db
    ```

    The `var/sqlite/app_db.sqlite` file is kept between deployments (shared folder). On the first deployment, it is empty.

### Order in which variables are read

| Priority | Source                                            | Usage                                |
|----------|---------------------------------------------------|--------------------------------------|
| 1        | environment variables (Docker, web server)   | `APP_ENV`, `DATABASE_URL` in production |
| 2        | `.env.local` file                              | production secrets, never committed |
| 3        | `.env` file                                    | development configuration       |

## Step 4: check, then deploy

Check that everything is ready (SSH connection, DNS, Docker):

```bash
frankendeploy doctor prod
```

Start the deployment:

```bash
frankendeploy deploy prod
```

!!! tip "Mac with Apple silicon (M1, M2...)"

    Your Mac (ARM) and the server (often x86_64) do not have the same architecture. If building the image is too slow, build it directly on the server:

    ```bash
    frankendeploy deploy prod --remote-build
    ```

Your application is online at `https://mon-app.fr` :tada:

For each new version, simply run `frankendeploy deploy prod` again.

## Importing your database

On the first deployment, the MariaDB database is empty. To import the content of your development database, export it from your computer (phpMyAdmin or dbgate) into an `app_db.sql` file, then send it to the server:

```bash
scp app_db.sql deploy@mon-serveur.fr:
```

Connect to the server over SSH and import the file into the `mon-app-db` container. The credentials are read from the container's variables: no password to type.

=== "MariaDB"

    ```bash
    docker exec -i mon-app-db sh -c 'exec mariadb -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"' < app_db.sql
    ```

=== "MySQL"

    ```bash
    docker exec -i mon-app-db sh -c 'exec mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' < app_db.sql
    ```

!!! info "The database credentials"

    FrankenDeploy stores them on the server in the `/opt/frankendeploy/apps/mon-app/shared/.db_credentials` file (readable with `sudo cat`).

!!! warning "Backups"

    FrankenDeploy only backs up the database automatically before Doctrine migrations, which Edu-Framework does not use. Remember to schedule backups yourself (`mariadb-dump`) on the server.

## Files kept between deployments

The application code is **in the Docker image**: on each deployment, the container is replaced. Only the shared files and folders (`shared_files` and `shared_dirs`) are kept:

| Path              | Content                                                     |
|-------------------|-------------------------------------------------------------|
| `.env.local`      | production environment variables (read-only)     |
| `var/logs`        | SQLite database of the server logs                             |
| `var/sessions`    | PHP sessions: users stay logged in           |
| `var/sqlite`      | the application's SQLite databases                               |
| `public/upload`   | files uploaded by users                       |

!!! danger "Anything written elsewhere is lost"

    If your application saves files in another folder, add it to `shared_dirs` **before** deploying. Be careful: defining `shared_dirs` replaces the whole list, so keep the folders above.

## Day-to-day use

| Need                                      | Command                                     |
|-------------------------------------------|---------------------------------------------|
| Deploy a new version                      | `frankendeploy deploy prod`                 |
| View the application logs                 | `frankendeploy logs prod`                   |
| Roll back to the previous version         | `frankendeploy rollback prod`               |
| Open a terminal in the container          | `frankendeploy shell prod`                  |
| Run a `bin/edu` command on the server     | `frankendeploy exec prod php bin/edu cache:clear` |
| Change a variable and restart             | `frankendeploy env set prod CLE=valeur --reload` |

## The production image

The project's `Dockerfile` contains three *stages*:

| Stage             | Usage                                                              |
|-------------------|--------------------------------------------------------------------|
| `frankenphp_base` | common base: PHP 8.4, extensions, Composer                       |
| `frankenphp_prod` | production: used by FrankenDeploy                            |
| `frankenphp_dev`  | development: used by `compose.yaml` (see [The application in Docker](../installation/docker.md)) |

The production image differs from the development image:

- the application is served over **HTTP on port 8080**: HTTPS is handled by the server's Caddy
- it runs as a **non-root** user (`app`)
- PHP uses the production configuration (`php.ini-production`, OPcache without file revalidation)
- the development dependencies and the Excimer profiler are not installed
- the debug bar is disabled (`APP_ENV=prod`)
- the `docker-entrypoint.sh` script waits for the database to be available before starting

You can build and test the production image on your computer:

```bash
docker build --target frankenphp_prod -t mon-app .
```

```bash
docker run --rm -p 8080:8080 mon-app
```

!!! warning "Do not delete or regenerate the project's Docker files"

    If the `Dockerfile`, `docker-entrypoint.sh` or `.dockerignore` are missing, FrankenDeploy generates new ones designed for Symfony: the image build will fail (`bin/console` command not found).

    For the same reason, do not run `frankendeploy build`, `frankendeploy init --force` or `frankendeploy dev` in an Edu-Framework project: these commands overwrite `Dockerfile`, `docker-entrypoint.sh`, `.dockerignore` and `compose.yaml`. To restore the files:

    ```bash
    git checkout -- Dockerfile docker-entrypoint.sh .dockerignore compose.yaml
    ```

## If you run into an error

### The healthcheck fails

FrankenDeploy displays the last 50 log lines of the container. Check that the `healthcheck_path` route responds without authentication, and that the environment variables are defined (`frankendeploy env list prod`).

### Database unreachable

The container waits 60 seconds for the database at startup. Check the `DB_*` variables with `frankendeploy env list prod` and the `DATABASE_URL` of a managed database with `frankendeploy exec prod printenv DATABASE_URL`.

### The HTTPS certificate is not issued

The domain's DNS record must point to the server and ports 80 and 443 must be open. `frankendeploy doctor prod` checks both of these points.
