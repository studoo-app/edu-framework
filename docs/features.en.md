# Features

Edu Framework offers a simplified, readable version of the building blocks of a professional framework such as Symfony. Each feature is an opportunity to **learn a concept** that you will later find as is in a mainstream framework.

This page presents all the features, grouped by learning theme, with for each one **the concept covered** and **its equivalent in Symfony**.

<div class="grid cards" markdown>

-   :material-sitemap: **MVC architecture**

    ---

    Split the code into layers: controller, model and view, around a request / response cycle.

    [:octicons-arrow-right-24: Architecture](#architecture-and-request-lifecycle)

-   :material-database: **Data**

    ---

    Connect to a database, write entities and repositories, build a CRUD.

    [:octicons-arrow-right-24: Data](#data)

-   :material-api: **API**

    ---

    Return JSON data with the right HTTP code and document your API in OpenAPI format.

    [:octicons-arrow-right-24: APIs](#apis)

-   :material-console: **CLI**

    ---

    Use `bin/edu` to generate code, and build your own command-line commands.

    [:octicons-arrow-right-24: The command line](#the-command-line-and-generators)

-   :material-email-outline: **Mail**

    ---

    Test sending emails with Mailpit: a local SMTP server and a web inbox, without sending any real message.

    [:octicons-arrow-right-24: Mail](#mail)

-   :material-docker: **DevOps**

    ---

    Run the database, the tools and the application in Docker, understand ports and environment variables.

    [:octicons-arrow-right-24: DevOps](#environment-and-devops)

-   :material-shield-lock: **Security**

    ---

    Prepared statements, CSRF token, errors hidden in production: good habits against web vulnerabilities.

    [:octicons-arrow-right-24: Security](#security)

-   :material-bug-check: **Debugging and performance**

    ---

    Debug bar, profiler, SQL queries and code profiler, just like in a professional framework.

    [:octicons-arrow-right-24: Debugging](#debugging-and-performance)

</div>

## Architecture and request lifecycle

| Feature | What you learn | In Symfony |
|----------------|---------------------|--------------|
| **Single entry point** (`public/index.php`) | All requests go through a single file that boots the framework (*front controller*) | `public/index.php` |
| **MVC architecture** | Separate the logic (controller), the data (model) and the display (view) | Same architecture |
| **PSR-4 autoloading** (Composer) | Load classes without `require`, organize code into *namespaces* | Same mechanism |
| **Configuration through `.env`** | Separate configuration from code, distinguish `dev` and `prod` modes (`APP_ENV`) | `.env` file, `APP_ENV` |

To go further: [The MVC architecture concept](build/concept-mvc.md) and [The project tree](build/arborescence.md).

## Routes and controllers

| Feature | What you learn | In Symfony |
|----------------|---------------------|--------------|
| **YAML routes** (`app/Config/routes.yaml`) | Map a URL and an HTTP method to a controller | `config/routes.yaml` or `#[Route]` attribute |
| **Route parameters** (`/user/{id:\d+}`) | Retrieve part of the URL and constrain it with a regular expression | `{id}` with `requirements` |
| **One controller, several routes** (`Controller\VilleController::index`) | Group the actions of the same resource in one class | One method per action in a controller |
| **URL generation from the route name** (`getNameToPath()`) | Never hard-code a URL in the code or templates | `path()` in Twig, `generateUrl()` |
| **The `Request` object** | Read parameters, the HTTP method, headers and uploaded files | `Symfony\Component\HttpFoundation\Request` |
| **File uploads** (`hasFile()`, `getFile()`, `move()`) | Receive, validate and store a file sent by a form | `UploadedFile` |

To go further: [Building a page](build/controller-edu.md), [Request](build/resquet.md) and [Generating a URL from its name](build/route.md).

## Views

| Feature | What you learn | In Symfony |
|----------------|---------------------|--------------|
| **Twig templates** (`app/Template`) | Separate HTML from PHP, use template inheritance and automatic escaping | Twig, identical |
| **Template cache** (`var/cache/twig`) | Understand template compilation and the role of a cache | `var/cache` |
| **`dd()` function** | Inspect a variable during development | `dd()` / `dump()` |

To go further: [Building a page](build/controller-edu.md) and [Building a form](build/controller-edu-post.md).

## Data

| Feature | What you learn | In Symfony |
|----------------|---------------------|--------------|
| **`DatabaseService`** (MySQL, MariaDB, SQLite) | Connect to a database with PDO, switch databases through configuration (`DB_TYPE`) | Doctrine DBAL |
| **Entities and repositories** | Represent a table with a class, isolate SQL queries in a repository | Doctrine ORM: `Entity` and `Repository` |
| **CRUD** | Create, read, update and delete data, from the database all the way to the form | `make:crud` |
| **The PRG pattern** (*Post/Redirect/Get*) | Redirect after a form to avoid submitting it twice | Same pattern (`redirectToRoute()`) |

To go further: [DatabaseService](build/dataservice.md) and [Building the CRUD](build/dataservice-select.md).

## APIs

| Feature | What you learn | In Symfony |
|----------------|---------------------|--------------|
| **JSON API controllers** | Return JSON data with the right HTTP code | `JsonResponse` |
| **OpenAPI documentation** (`OpenApi\Attributes` attributes) | Describe an API so that it can be tested and shared | NelmioApiDocBundle, API Platform |

To go further: [Building an API](build/controller-edu-api.md).

## The command line and generators

The `php bin/edu` command gathers the developer's tools, like `bin/console` in Symfony.

| Command | What it does | In Symfony |
|----------|-----------------|--------------|
| `start` | Starts the development server (`--port`, `--no-start` options) | `symfony server:start` |
| `check:config` | Checks the prerequisites (PHP version, extensions) | `symfony check:requirements` |
| `make:controller` | Generates a controller and its template | `make:controller` |
| `make:entity` | Generates an entity and its repository | `make:entity` |
| `make:api` | Generates a documented API controller | `make:controller` + API Platform |
| `make:command` | Generates a CLI command | `make:command` |
| `cache:clear` | Clears the application cache | `cache:clear` |

Building your own commands is also a way to approach automation, and the use of a CLI tool by an AI agent.

To go further: [The bin/edu command](installation/command-edu.md) and [Building a CLI command](build/new-command-edu.md).

## Security

Edu Framework is used in cybersecurity lab sessions: deliberately simple and transparent, it lets you **understand a vulnerability, then the good practice that fixes it**.

| Feature | Vulnerability prevented | In Symfony |
|----------------|------------------|--------------|
| **Prepared statements** (PDO) | SQL injection | Doctrine parameterized queries |
| **CSRF token** (`csrf_token()`, `isCsrfTokenValid()`) | Cross-site request forgery (CSRF) | `csrf_token()` and `isCsrfTokenValid()`, same names |
| **Twig escaping** | Script injection (XSS) | Twig automatic escaping |
| **Error messages hidden in production** | Leak of technical information | Production error pages |
| **Debug routes only in `dev`** | Exposure of internal tools | Profiler disabled in production |

To go further: [Protecting a form (CSRF)](build/csrf.md) and [HTTP errors](build/http-error.md).

## Error handling

| Feature | What you learn | In Symfony |
|----------------|---------------------|--------------|
| **Customizable error pages** (403, 404, 405, 500) | HTTP status codes and what they mean | `error404.html.twig` templates, ... |
| **`ErrorHttpStatusException`** | Raise an HTTP error from a controller (Example: access forbidden) | `HttpException`, `createAccessDeniedException()` |
| **Exception message in dev mode** | Read an error to fix it, without exposing it in production | Dev mode exception page |

To go further: [HTTP errors](build/http-error.md).

## Debugging and performance

In development mode (`APP_ENV=dev`), Edu Framework provides tools inspired by Symfony's *Web Debug Toolbar* and *Profiler*.

| Feature | What you learn | In Symfony |
|----------------|---------------------|--------------|
| **Debug bar** | Read at a glance the status, route, time and memory of a request | Web Debug Toolbar |
| **Profiler and request history** | Find and filter past requests, along with their errors | Profiler (`/_profiler`) |
| **SQL queries per HTTP request** | Spot a slow or repeated SQL query ("N+1" problem) | Profiler's Doctrine panel |
| **Code profiler** (Excimer, built-in flamegraph) | Measure the time spent in each function, read and compare flamegraphs | Blackfire, *Performance* panel |

To go further: [The debug bar and the profiler](build/debug-bar.md).

## Mail

Sending an email from an application (registration confirmation, forgotten password, ...) can be tested without the risk of writing to real people: the framework's Docker services include [Mailpit](https://github.com/axllent/mailpit){:target="_blank"}, which **captures all sent emails**.

| Feature | What you learn | In Symfony |
|----------------|---------------------|--------------|
| **Local SMTP server** (port `1025`) | The SMTP protocol and configuring email sending through an environment variable (`MAILER_DSN`) | `MAILER_DSN` of the Mailer component |
| **Web inbox** ([http://localhost:8025](http://localhost:8025){:target="_blank"}) | Check the content, recipient and formatting of an email without actually sending it | Mailpit, built into `symfony-docker` |
| **Access from the debug bar** (DEV block) | Find the development tools from within the application | Profiler's *E-mails* panel |

```dotenv
MAILER_DSN="smtp://localhost:1025"
```

!!! info "Sending the email"

    The framework provides the test server, not the sending itself: use an email library such as [Symfony Mailer](https://symfony.com/doc/current/mailer.html){:target="_blank"}, configured with the `MAILER_DSN` variable.

To go further: [Starting the services](installation/start-services.md).

## Environment and DevOps

| Feature | What you learn | In Symfony |
|----------------|---------------------|--------------|
| **Docker services** (MariaDB, Mailpit, phpMyAdmin, DbGate) | Run a database and tools without installing anything on your machine | Symfony's Docker Compose |
| **Application in Docker** (FrankenPHP, local HTTPS) | Containerize an application, understand ports and environment variables | `symfony-docker` (FrankenPHP) |
| **Versioning your project** (Git) | Track history and work as a team | Same tool |

To go further: [Starting the services](installation/start-services.md), [The application in Docker](installation/docker.md) and [How to version your project](installation/versionning.md).

## The learning path

The documentation is organized as a progressive path:

1. **[Get started](installation/index.md)**: install the environment, create and run your project.
2. **[Build](build/index.md)**: a page, a form, the CRUD, a CLI command, an API, then dig deeper into the request, data, routes, errors and debugging.

The learning objectives of each stage are detailed on the [Learning objectives](about.md) page.

!!! warning "Important information"

    **This framework is not suitable for production use. It is intended for educational purposes.**
