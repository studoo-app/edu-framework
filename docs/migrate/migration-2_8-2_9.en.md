# Migrating from v2.8 to v2.9

## Introduction
Version 2.9 of the framework brings two new features: **deploying the application to a server** (Debian or Ubuntu) with the `make:deploy` command, and the **built-in flamegraph** in the code profiler. \
It is **backward compatible**: your application works after updating the framework, without changing your code (do read, however, [the change in how environment variables are loaded](#loading-environment-variables)). The other steps on this page prepare your project for deployment and for the new Docker files.
Link to the release notes: [v2.9](../changelog.md#v290-21102026)

!!! info "The PHP version does not change"

    Like v2.8, v2.9 requires at least **PHP 8.4**. If your project works with v2.8, your PHP version is fine.

!!! tip "Coming from an older version?"

    First do the [migration from version 2.3 to version 2.8](migration-2_3-2_8.md), then follow this page.

## How to migrate?
To migrate from version 2.8 to version 2.9, follow these steps:

1. **Analyze the changes**: read the [Changes](#changes) and [New features](#new-features) sections to understand their impact on your project.
2. **Version your work**: make a commit before you start, so you can roll back (`git status` must show nothing). The database is not modified by this migration.
3. Carry out the following actions:

3.1 **Update the framework**:
```Bash
composer update studoo/edu-framework
```

Result:
```bash
[...]
Package operations: 0 installs, 1 update, 0 removals
  - Upgrading studoo/edu-framework (dev-main xxxxxxx => dev-main yyyyyyy): Extracting archive
[...]
```

The commit identifiers `xxxxxxx` and `yyyyyyy` vary: the installed version is checked in step 3.5.

3.2 **Update the Docker files**:

v2.9 splits the `Dockerfile` into three *stages*: a **development** image, identical to the v2.8 one, and a **production** image, used for deployment. The production image needs two new files: `docker-entrypoint.sh` and `.dockerignore`.

Copy the three files from the framework installed by Composer in step 3.1 (on Windows, in a git bash terminal):

```bash
cp vendor/studoo/edu-framework/Dockerfile vendor/studoo/edu-framework/docker-entrypoint.sh vendor/studoo/edu-framework/.dockerignore .
```

!!! tip "Have you customized your Dockerfile?"

    The copy replaces your `Dockerfile`. Retrieve the old version, saved by the commit in step 2, with `git show HEAD:Dockerfile`, then carry your changes over to the `frankenphp_base` stage (extensions and packages shared by both images) or to the `frankenphp_dev` stage (development tools only). The name of the `frankenphp_prod` stage must not change: FrankenDeploy builds the image with this name.

In the `compose.yaml` file, the `frankenphp` service must build the development image and pass the `EDU_PROFILER` argument (code profiler). In projects created with the skeleton, it contains the short form `build: .` on a single line: replace it entirely with the `build:` block below, at the same indentation level as `container_name:`.

```diff
  frankenphp:
    container_name: ${APP_NAME}-http-server
--  build: .
++  build:
++    context: .
++    # Development stage of the Dockerfile (frankenphp_prod is used for deployment)
++    target: frankenphp_dev
++    args:
++      EDU_PROFILER: ${EDU_PROFILER_BUILD:-1}
```

!!! warning "Do not add lines under `build: .`"

    `target` and `args` cannot be added under the short form `build: .`: Docker Compose rejects the file (`did not find expected key`). If your `build:` key already contains `context` and `args`, only add the `target` line. Without the `args` block, the `EDU_PROFILER_BUILD` variable in `.env` has no effect.

Rebuild the development image if you use the [100% Docker workflow](../installation/docker.md):

```Bash
docker compose up -d --build
```

3.3 **Update the `.gitignore` file**:

The `composer.lock` file **must** now be versioned: the production image installs exactly the versions it contains, and FrankenDeploy only sends the files tracked by git to the server. Remove the `composer.lock` line from `.gitignore`.

v2.9 also reads the `.env.local` file, which contains the production secrets: it must **never** be versioned. Check that the `.env.local` and `.env.*.local` lines are present (this is already the case in projects created with the skeleton), and add them if they are missing.

```diff
  .env
  .env.local
  .env.*.local
--composer.lock
```

Then add the `composer.lock` file to the repository:

```Bash
git add .gitignore composer.lock
```

3.4 **Fix the line endings of shell scripts (Windows)**:

!!! danger "The production container does not start"

    On Windows, git may convert the line endings of the `docker-entrypoint.sh` file to the Windows format (CRLF). The production container then refuses to start with the error `exec /usr/local/bin/docker-entrypoint: no such file or directory`.

Create the `.gitattributes` file at the root of the project, so that git always keeps Linux line endings (LF) for shell scripts:

```text
*.sh text eol=lf
```

Then normalize the files that are already there:

```Bash
git add .gitattributes
git add --renormalize .
```

This step has no effect on macOS and Linux, but protects team members who work on Windows.

3.5 **Clear the cache and check the version**:
```Bash
php bin/edu cache:clear
php bin/edu --version
```

Result:
```bash
EduFramework v2.9.0
```

The new `make:deploy` command appears in the list of commands:

```Bash
php bin/edu list make
```

Finally, version the migration:

```Bash
git add Dockerfile docker-entrypoint.sh .dockerignore compose.yaml
git commit -m "Migrate to Edu-Framework v2.9"
```

## Changes

### **Loading environment variables**
Environment variables are now loaded by the `ConfigCore::loadEnv()` method, used by the application (`public/index.php`) and by the `bin/edu` command. They are read in this order (the first value found is kept):

| Priority | Source                                                   | Usage                                   |
|----------|----------------------------------------------------------|-----------------------------------------|
| 1        | process environment variables (Docker, shell)            | `APP_ENV`, `DATABASE_URL` in production |
| 2        | `.env.local` file (new)                                  | production secrets, never versioned     |
| 3        | `.env` file                                              | development configuration               |

Consequences for your project:

- the `.env` and `.env.local` files are **optional**: the application and `bin/edu` start without them (in production, the Docker image contains no `.env`);
- a variable defined in your terminal or by Docker **overrides** the one in `.env` (Example: `APP_ENV=prod php bin/edu start` starts in production mode);
- with `php bin/edu start`, the variables in `.env` and `.env.local` are read at startup and passed to the PHP server: **after changing these files, stop the server (++ctrl+c++) and run `php bin/edu start` again** (in v2.8, the change was picked up on the next request);
- `ConfigCore::getEnv()` returns an empty string for a missing variable, without a PHP warning. Use `ConfigCore::existEnv()` to find out whether a variable is defined.

!!! note "The DATABASE_URL variable"

    If the `DATABASE_URL` variable is defined (format `mysql://utilisateur:motdepasse@hote:3306/base`), the framework derives from it the `DB_HOST_STATUS`, `DB_TYPE`, `DB_HOST`, `DB_SOCKET`, `DB_USER`, `DB_PASSWORD` and `DB_NAME` variables **that are not already defined**. The `DB_*` variables in your `.env` therefore keep priority.

    A malformed MySQL, MariaDB or PostgreSQL `DATABASE_URL` stops the application with the message « La variable DATABASE_URL est invalide » ("The DATABASE_URL variable is invalid"). A `DATABASE_URL` of another type (Example: `sqlite:///...` from a Symfony project) is ignored.

### **The bin/edu command**
`bin/edu` loads environment variables even without a `.env` file. If `DB_HOST_STATUS=true` is defined (in `.env`, `.env.local` or the environment), the command connects to the database at startup, as in v2.8: the database must therefore be running.

### **The multi-stage Dockerfile**
The `Dockerfile` now contains three stages:

| Stage             | Usage                                                              |
|-------------------|--------------------------------------------------------------------|
| `frankenphp_base` | common base: PHP 8.4, extensions, Composer                         |
| `frankenphp_prod` | production: used by FrankenDeploy (see [Deploying your application](../deploy/index.md#the-production-image)) |
| `frankenphp_dev`  | development: used by `compose.yaml`, identical to the v2.8 image   |

The development image does not change: the 100% Docker workflow and the `docker compose` commands stay the same.

## New features

### **Deploying your application** (v2.9)
The `php bin/edu make:deploy` command generates the `frankendeploy.yaml` file, which describes how to deploy the application to a Debian or Ubuntu server with [FrankenDeploy](https://yoanbernabeu.github.io/frankendeploy/){:target="_blank"}: automatic HTTPS, managed MariaDB database, zero-downtime updates and rollback to the previous version.

```Bash
php bin/edu make:deploy --domain=mon-app.fr
```

For more details, see the page [Deploying your application](../deploy/index.md).

### **The flamegraph built into the profiler** (v2.9)
The **Code** tab of the profiler displays the profile of a request without any file to download, with three new views:

- **Flamegraph**: calls grouped together, the most expensive on the left;
- **Chronological**: the order of the calls, with a time axis;
- **Compare**: the differential flamegraph of two profiled requests (slower parts in red, faster parts in blue).

The **Code** block of the debug bar also displays a **mini-flamegraph**: clicking it opens it full size in the profiler.

For more details, see the sections [The built-in flamegraph](../build/debug-bar.md#the-built-in-flamegraph) and [Comparing two requests](../build/debug-bar.md#comparing-two-requests).
