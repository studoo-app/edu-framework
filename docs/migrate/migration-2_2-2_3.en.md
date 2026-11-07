# Migrating from v2.2 to v2.3

## Introduction
Version 2.3 of the framework comes with its share of new features: debug bar, profiler, logging of requests in SQLite and a new PHP prerequisite. \
Please read the changes carefully to migrate your project from version 2.2 to version 2.3.
Link to the release note: [Release note 2.3](../changelog.md#v230-05102026)

## How to migrate?
To migrate from version 2.2 to version 2.3, follow these steps:

1. **Analyze the changes**: Analyze the changes brought by the new version to understand their impact on your project.
2. **Back up your data**: Make sure you back up your data before migrating to avoid any loss.
3. Carry out the following actions:

3.1 **Update your PHP version**:

!!! warning "Required version"

    From version **v2.3**, the framework requires at least **PHP 8.4** (v2.2 required PHP 8.2).
    We recommend installing version 8.4 or higher (8.5).

| | Minimum version | Recommended |
|--------|-------------|-----------------|
| v2.2   | PHP 8.2     | 8.2 or higher   |
| v2.3   | **PHP 8.4** | 8.4 or higher (8.5) |

To install or update PHP, follow the chapter ["Development environment"](../installation/prerequis.md#development-environment).

3.2 **Check the PHP extensions**:

The debug bar and the profiler use **SQLite** to record the HTTP requests received by the server.
Check that the `pdo_sqlite` extension is enabled (in addition to `openssl`, `mbstring` and `pdo_mysql`):

```Bash
php -m | grep -i sqlite
```

!!! note "Expected result"

    ```Bash
    pdo_sqlite
    sqlite3
    ```

If the extension is not enabled, see the chapter ["Enabling extensions"](../installation/prerequis.md#enabling-extensions).

3.3 **Update the composer file**:

```diff
  "require": {
--    "php": ">=8.2",
++    "php": ">=8.4",
     "ext-mbstring": "*",
     "studoo/edu-framework": "2.x-dev"
  },
```

3.4 **Update the framework**:
```Bash
composer update studoo/edu-framework
```

Result:
```bash
[...]
Package operations: 0 installs, 1 update, 0 removals
  - Upgrading studoo/edu-framework (2.2.x-dev xxxxxxx => 2.3.x-dev xxxxxxx): Extracting archive
[...]
```

3.5 **Check the framework version**:
```Bash
 php bin/edu --version
```

Result:
```bash
EduFramework v2.3.x-xxxx
```

## Changes

### **Dependency security**
The `symfony/yaml` dependency moves to version **6.4** to fix three known security vulnerabilities in 6.3:

- CVE-2026-45304: exponential memory allocation through recursive alias expansion ("Billion Laughs")
- CVE-2026-45305: ReDoS through catastrophic backtracking in `Parser::cleanup()`
- CVE-2026-45133: stack exhaustion through unbounded recursion

The update via `composer update studoo/edu-framework` applies this fix automatically; no further action is needed.

### **HTTP codes of error pages**
The framework's error controllers (`HttpError404Controller`, `HttpError405Controller`, ...) now send the **matching HTTP status code** (`404`, `405`, ...) instead of `200`. \
If your project has unit tests that check the rendering or status of error pages, they may need to be updated.

### **Debug mode routes**
The `/_debug/profiler` and `/edu-logs` routes are registered **only in development mode** (`APP_ENV=dev`). \
They are therefore no longer accessible when `APP_ENV=prod`. If your application declared its own routes with these URIs, they are now fully available in production mode.

## New features

### **The debug bar and the profiler**
In development mode (`APP_ENV=dev`), a **debug bar** is displayed at the bottom of each page (HTTP status, route, execution time, memory, DEV, ...) and a **profiler page** lets you browse the history of HTTP requests.

For more details, see the page [The debug bar and the profiler](../boost/debug-bar.md).

### **The options of the start command**
The `start` command accepts new options to adapt the startup to your needs:

```bash
php bin/edu start --port=8080
```

| Option            | Description                                                           |
|-------------------|-----------------------------------------------------------------------|
| `-p` or `--port`  | Changes the listening port of the development server (default: `8042`) |
| `--no-start`      | Checks the prerequisites of your environment without starting the server |

For more details, see the page [Start/Stop the application](../installation/start-application.md).

### **The MailPit service**
The **MailPit** mail service replaces Mailcatcher. It acts as an SMTP server (port `1025`) to test sending emails from your application, and provides a web interface (port `8025`) to view the emails sent.

The `MAILER_DSN` environment variable in your `.env` file must point to this service:

```dotenv
MAILER_DSN="smtp://localhost:1025"
```

### **The dbgate service**
The [dbgate](https://dbgate.org){:target="_blank"} service is added to the `compose.yaml` file to manage your databases (MySQL and SQLite) from a web interface, alongside PHPMyAdmin.

To get the new `compose.yaml` file:

=== ":fontawesome-brands-windows: WINDOWS"

    Open a git bash terminal at the root of your project

    To download the compose file, follow the instructions:

    ```bash
    curl -o compose.yaml https://raw.githubusercontent.com/studoo-app/edu-framework/2.x/compose.yaml
    ```

    !!! bug "curl: (35)"

        If you get the error `curl: (35) schannel: next InitializeSecurityContext failed: Unknown error (0x80092012) - The revocation function was unable to check revocation for the certificate.`

        Type the following command:

        ```bash
        curl --ssl-no-revoke -o compose.yaml https://raw.githubusercontent.com/studoo-app/edu-framework/2.x/compose.yaml
        ```

=== ":fontawesome-brands-apple: MAC OS"

    Open a terminal at the root of your project

    To download the compose file, follow the instructions:

    ```bash
    curl -sS https://raw.githubusercontent.com/studoo-app/edu-framework/2.x/compose.yaml -o compose.yaml
    ```

Then restart the services:

```Bash
docker compose up -d
```

For more details, see the page [How to install and manage the services](../installation/start-services.md).
