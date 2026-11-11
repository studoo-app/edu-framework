# CHANGELOG

**Overview of the Edu Framework releases**

## v2.13.0 - 11/11/2026

**features**

- [#198](https://github.com/studoo-app/edu-framework/issues/198) Routes declared with PHP attributes: `#[Route('/hello/{name}', methods: ['GET', 'POST'], name: 'hello_name')]` on a controller method and `#[RouteGroup('/admin', namePrefix: 'admin_')]` on the class (`studoo/edu-route` attributes). The controllers of `app/Controller` (subfolders included) are read on each request, after `app/Config/routes.yaml`: both ways work together and `routes.yaml` becomes optional as soon as a controller declares a route
- `getNameToPath()` (Twig and `Route` class) generates the URL of every named route, from `routes.yaml` or from attributes: it relies on the routes declared to the routing engine instead of reading `routes.yaml` again
- `php bin/edu routes:list` also lists the routes declared with attributes

**maintenance**

- `FastRouteCore::loadRoutes()` loads `routes.yaml` then the attributes (used by `LoadCouchCore` and `routes:list`); `FastRouteCore::loadRouteAttributes()` and `FastRouteCore::findAttributeControllers()` are public
- New optional configuration keys `controller_path` (default: `app/Controller/` under `base_path`) and `controller_namespace` (default: `Controller\`)
- `Route::getRouteInfo()` only returns named routes (key, `uri`, `controller`, `httpMethod`, `uri_parse`); `Route::loadRoute()` still returns the raw content of `routes.yaml`

**documentation**

- new [Declaring a route with #[Route]](build/route-attribute.md) page; update [Building a page](build/controller-edu.md), [Route](build/route.md) and [The bin/edu command](installation/command-edu.md)

## v2.12.0 - 07/11/2026

**features**

- [#197](https://github.com/studoo-app/edu-framework/pull/197) New `php bin/edu routes:list` command: lists the routes of `app/Config/routes.yaml` (method, URI, controller, name), one row per HTTP method; the `--method` option filters on one HTTP method

**maintenance**

- Routing: `studoo/edu-route` is upgraded to `^1.1` (list of declared routes). The route name (key of `routes.yaml`) is now passed to the routing engine; `FastRouteCore::addRoute()` takes an optional fourth `$name` parameter and `FastRouteCore::declaredRoutes()` returns the declared routes

**documentation**

- update [The bin/edu command](installation/command-edu.md): `routes:list` command

## v2.11.0 - 06/11/2026

**maintenance**

- [#187](https://github.com/studoo-app/edu-framework/pull/187) Routing: the framework uses [`studoo/edu-route`](https://github.com/studoo-app/eduRoute) (`^1.0`), a fork of `nikic/fast-route` modernised for PHP 8.4 (no version of `nikic/fast-route` has been released since 1.3.0 in 2018). **No change for applications**: the format of `app/Config/routes.yaml`, the URI syntax (`/user/{id:\d+}[/{name}]`), the HTTP methods (case is preserved: a `[get]` route does not respond to a `GET` request), the 404 and 405 pages, the route variables (`$request->get('id')`) and `getNameToPath()` work as before
- Dependencies: `studoo/edu-route` replaces `nikic/fast-route` and adds `psr/http-message` and `psr/simple-cache`; it supports PHP 8.4 and 8.5
- Code that uses the routing engine directly:
    - `FastRouteCore::getDispatcher()` returns a `Studoo\EduRoute\Dispatcher` (instead of `FastRoute\Dispatcher`); its `dispatch()` returns a `Matched`, `MethodNotAllowed` or `NotMatched` object (properties `handler`, `variables`, `allowedMethods`) instead of an array: accessing `$result[0]` still works but is deprecated, use `instanceof`
    - an invalid route (duplicate route, repeated parameter, capturing group) throws `Studoo\EduRoute\BadRouteException` instead of `FastRoute\BadRouteException`, with the same message; `Route::getNameToPath()` also throws it for an invalid URI. It is different from `Studoo\EduFramework\Core\Exception\BadRouteException` (unknown route, missing parameter), which does not change

**bug Fixes**

- `routes.yaml`: a badly described route (missing `uri`, `controller` or `httpMethod` key, a list instead of a string) throws an `ErrorConfigException` that names the route (`La route <hello> du fichier routes.yaml est invalide : la clé 'controller' est absente…`, displayed in `dev` mode) instead of a PHP error
- `routes.yaml`: an empty value (`~` or a lone dash) in the `httpMethod` list no longer emits a deprecation warning under PHP 8.5

**documentation**

- update [Building a page](build/controller-edu.md): the three keys of a route

## v2.10.1 - 06/11/2026

**bug Fixes**

- `base.html.twig` template (application and the framework's internal template): the block was called `titre` whereas pages define `{% block title %}`, so the `<title>` tag stayed empty. The block is renamed `title` (same fix in `studoo/edu-framework-skeleton`)

**maintenance**

- Tests: added example tests for the application controllers (`tests/App/Controller`) and updated the HTML fingerprints in `FastRouteCoreTest`

**documentation**

- new [Testing your application (unit tests)](build/tests-unitaires.md): approach, installing and configuring PHPUnit, testing an entity, a repository on an SQLite test database and a controller, DataProvider, common errors and exercises
- update [The directory structure](build/arborescence.md): `tests` folder and `phpunit.xml` file

## v2.10.0 - 06/11/2026

**new features**

- Profiler: the request details page displays the **GET, POST and session variables** as tables. `HttpRequestLogger` records `$_GET` and `$_POST` at the start of the request and `$_SESSION` at the end of the request (`get`, `post` and `session` keys of the log). Sensitive variables (name containing `pass`, `pwd`, `secret`, `mot_de_passe` or `mdp`) are masked, and objects in the session are recorded with all their properties and a `__class` key
- **Logs** view: the GET / POST / session variables of the context are displayed as JSON

**bug Fixes**

- [#174](https://github.com/studoo-app/edu-framework/issues/174) Symfony dependencies: `symfony/yaml`, `symfony/console`, `symfony/filesystem` and `symfony/process` move to **stable 7.4 LTS** (`^7.4`) instead of `x-dev` branches. `composer.json` enables `prefer-stable` so that development branches are no longer resolved when a stable version exists
- `bin/edu` console: `Application::add()`, deprecated in Symfony 7.4, is replaced by `addCommand()`
- Profiler: an error while recording the session can no longer make the request fail

**maintenance**

- Tests: migration from PHPUnit 9 to **PHPUnit 13** (`phpunit/phpunit: ^13`) and of `phpunit.xml` to the 13.4 schema (the `<coverage><include>` section becomes `<source><include>`)
- Tests: `HttpErrorControllerTest` resets the HTTP response code to 200 after each test, so that it no longer skews `HttpRequestLoggerTest` depending on the execution order

**documentation**

- update [The debug bar and the profiler](build/debug-bar.md): description of the `get`, `post` and `session` keys of a request log

## v2.9.1 - 05/11/2026

**bug Fixes**

- Fixed the symfony/filesystem[7.2.x-dev] package

## v2.9.0 - 21/10/2026

**new features**

- Profiler: **built-in flamegraph** in the **Code** tab, with no file to download. Three views are added to the function table: **Flamegraph** (grouped calls, most expensive on the left, `#code/{token}/flame`), **Chronologique** (time order: order of calls with a time axis, `#code/{token}/time`) and **Comparer** (compare: differential flamegraph of two profiled requests, slower parts in red and faster parts in blue, `#code/{token}/diff/{autre}`). Colours indicate where the code comes from (`app` / `framework` / `vendor`). You can zoom with a click, go back up via the call path or with Esc, search for a function (with the total time found), and display a tooltip and the function details, with a link to its callers and callees. Calls under 0.1% are grouped into « … (autres) » (others)
- Debug bar: **mini-flamegraph** in the **Code** block panel; a click opens it full size in the profiler
- `GET /edu-code-profile` endpoint: new `format=flame` with the parameters `order` (`heavy` / `time`), `compare` (reference token), `min` (pruning) and `depth` (depth); new methods `CodeProfileAggregator::flameTree()` and `CodeProfileAggregator::diffTree()`
- Deployment to a Debian / Ubuntu server with [FrankenDeploy](https://yoanbernabeu.github.io/frankendeploy/): new `php bin/edu make:deploy` command that generates the `frankendeploy.yaml` file (options `--domain`, `--name`, `--database=mariadb|mysql|sqlite|none`, `--force`) with the folders shared between deployments (`.env.local`, `var/logs`, `var/sessions`, `var/sqlite`, `public/upload`)
- Multi-stage `Dockerfile`: `frankenphp_base` (common base), `frankenphp_prod` (production image: HTTP on port 8080, non-root user `app`, `php.ini-production`, OPcache without revalidation, sessions in `var/sessions`, without Excimer or dev dependencies, healthcheck) and `frankenphp_dev` (development image, explicitly targeted by `compose.yaml`). New `docker-entrypoint.sh` script that waits for the database before starting FrankenPHP
- Environment variables: new `ConfigCore::loadEnv()` method used by the application and by `bin/edu`. The `.env` and `.env.local` files are optional (`.env.local` takes precedence over `.env`), process environment variables (Docker) take precedence and are read by `ConfigCore::getEnv()` / `existEnv()` even when `variables_order` does not contain `E` (the case with `php.ini-production`). If `DATABASE_URL` is defined (`mysql://user:pass@host:3306/db`), missing `DB_*` variables are derived from it; a malformed MySQL / MariaDB / PostgreSQL `DATABASE_URL` (Example: password containing an unencoded `#`) throws an explicit `ErrorConfigException`

**bug Fixes**

- `ConfigCore::getEnv()` no longer raises a warning for a missing variable (returns an empty string)
- `bin/edu` starts without a `.env` file
- Tests: `APP_ENV` inherited from the shell or from the development container no longer affects the tests (`tests/bootstrap.php`)

**documentation**

- update [The debug bar and the profiler](build/debug-bar.md): new sections "The built-in flamegraph" and "Comparing two requests" (with screenshots), debug bar mini-flamegraph, API parameters `format=flame`, `order`, `compare`, `min` and `depth`
- new [Migrating from version 2.8 to version 2.9](migrate/migration-2_8-2_9.md): updating the framework, Docker files (multi-stage `Dockerfile`, `docker-entrypoint.sh`, `.dockerignore`, `target` in `compose.yaml`), `.gitignore` (`.env.local`, `composer.lock`), `.gitattributes` for shell scripts, changes to how environment variables are loaded
- new [Deploying your application](deploy/index.md): server preparation, `make:deploy`, environment variables, deployment, database import, shared folders, everyday commands, production image
- update [The bin/edu command](installation/command-edu.md): `make:deploy` command
- update [The application in Docker](installation/docker.md): link to the production image

## v2.8.0 - 20/10/2026

**new features**

- Code profiler in the debug bar: the time spent in each PHP function of an HTTP request is measured by the [Excimer](https://www.mediawiki.org/wiki/Excimer) extension (sampling profiler every 0.1 ms, compatible with FrankenPHP in classic and worker mode). Profiling is **on demand** (`EDU_PROFILER=trigger` by default): the new **Code** block of the debug bar sets the `edu_profile=1` cookie (or the `?_profile=1` parameter) and stays active until you click **Désactiver** (Disable); `EDU_PROFILER=on` profiles every request, `off` disables the profiler. The profile is attached to the request token and stored compressed in the `serv_code_profiles` table (`EDU_PROFILER_KEEP` profiles kept, 50 by default). The block shows the profiled time and the top 3 functions; the profiler's new **Code** tab (`/_debug/profiler#code/{token}`) lists all functions (self / inclusive time, filter by origin `app` / `framework` / `vendor`), their callers and callees, and exports the profile in Speedscope format for a flamegraph. New internal endpoint `GET /edu-code-profile` (dev mode only). The Docker image installs Excimer (build argument `EDU_PROFILER`, `EDU_PROFILER_BUILD=0` to exclude it) and `php bin/edu check:config` reports the extension as recommended
- Profiler: the summary section of a request now only shows the URL; the detailed information (method, HTTP status, IP, SAPI, duration, memory, date, token) has been removed from the header to lighten the display

**documentation**

- new [Features](features.md): all the framework's features grouped by learning topic, with the concept practised and its Symfony equivalent
- new [Migrating from version 2.3 to version 2.8](migrate/migration-2_3-2_8.md): migration steps (backup and switch from MySQL to MariaDB, error templates, Twig cache, `.env` variables, Docker files), changes and new features in versions 2.4 to 2.8
- update [The debug bar and the profiler](build/debug-bar.md): new section "The code profiler" (installation, modes, reading the profile with screenshots, reading the flamegraph with Speedscope, API)
- update [Installing with Docker](installation/docker.md): environment variables `EDU_PROFILER`, `EDU_PROFILER_KEEP` and `EDU_PROFILER_BUILD`
- update [Prerequisites](installation/prerequis.md): installing the optional `excimer` extension outside Docker

## v2.7.3 - 17/10/2026

**new features**

- Profiler: the Search view (`/_debug/profiler#search`) gets the **same status-family filter tabs as the logs view** — `Tous / 2xx / 3xx / 4xx / 5xx` (All / 2xx / ...), with the number of requests per family as a badge. A click filters the results by the selected family (the `Tous` tab shows all requests), the active tab is highlighted and the **Statut** (Status) field of the search form is kept in sync; the counters come from the `/edu-logs` API (computed server-side, they cover all requests matching the other filters) and remain independent of the status filter, as in the logs view

**bug Fixes**

- Debug bar: fixed the alignment of columns containing links (DbGate, phpMyAdmin, Mailpit) in the panel

**documentation**

- update [The debug bar and the profiler](build/debug-bar.md): status-family filter tabs documented for the **Logs** and **Search** views (click to filter, active tab, syncing of the form's Status field)

## v2.7.2 - 17/10/2026

**bug Fixes**

- Debug bar: final fix for the alignment of the close button on the right (flex styles, height and alignment)

## v2.7.1 - 17/10/2026

**bug Fixes**

- Debug bar: fixed the offset of the close button on the right, improved CSS isolation for buttons

## v2.7.0 - 17/10/2026

**new features**

- Debug bar: the **DEV** block lists the development services with their clickable URLs (HTTP/HTTPS application, MariaDB, Mailpit and its SMTP, DbGate, phpMyAdmin) — ports read from the `.env` (defaults from `compose.yaml`), variables passed to the `frankenphp` container
- Running the application in Docker via [FrankenPHP](https://frankenphp.dev){:target="_blank"}: new `frankenphp` web service in `compose.yaml` (PHP 8.4, HTTPS and HTTP/3), `Dockerfile` with the extensions required by the framework, `Caddyfile` driven by `SERVER_NAME`, `.env` variables passed to the container (network overrides `DB_HOST`, `DB_SOCKET`, `MAILER_DSN`), containers prefixed with `APP_NAME`, ports and `SERVER_NAME` configurable in the `.env` (local HTTPS mode: `SERVER_NAME=localhost`), MariaDB healthcheck — the classic `php bin/edu start` workflow is unchanged

**bug Fixes**

- Profiler: requests served by FrankenPHP were not recorded in the log database — only the built-in PHP server (`php bin/edu start`) filled it, without any per-request performance indicator. The new `HttpRequestLogger` class now logs **every request handled by the framework, whatever the SAPI** (built-in server, FrankenPHP, FPM, ...), with the originating server (`sapi`, displayed and filterable in the profiler via the **SAPI** field and the `sapi` parameter of the `/edu-logs` API), the execution time (`execution_time_ms`) and the memory used (`memory_mb`) per request, measured in the same way as the debug bar. Like the Symfony profiler, the line is reserved at the start of the request and then completed at the end: the DevTool block of the debug bar displays the request token and links to the new **request details** page (`#request/{token}`: status, method, URL, IP, SAPI, duration, memory, raw line). The `php bin/edu start` command no longer writes to the database (console display only): only requests executed by PHP are logged (static files served directly by the web server are excluded), and the database shared between servers is protected against locks (`PRAGMA busy_timeout`). The profiler's pagination bar is removed: navigation is done through the filters, the « Résultats » (Results) selector and the « 10 derniers » (Last 10) / « Dernier » (Latest) shortcuts (the API's `page` parameter is still supported)

**documentation**

- new [The application in Docker](installation/docker.md): architecture, start/stop, environment variables, ports and running several projects side by side, local HTTPS mode (self-signed certificate, `https://127.0.0.1` limitation), checking prerequisites in the container, troubleshooting
- update [Starting the services](installation/start-services.md): MySQL becomes MariaDB, FrankenPHP service in the ports table, commands per version, link fixes
- update [The debug bar and the profiler](build/debug-bar.md): collection of all requests by `HttpRequestLogger` whatever the server (`sapi` key, execution time and memory per request), request token in the DevTool block, new request details page (`#request/{token}`), profiler available on both servers (`http://localhost:8042/_debug/profiler` and `https://localhost/_debug/profiler`), limitations (static files not logged, Docker network IP, FrankenPHP worker mode); list of development services (clickable URLs) in the DEV block

## v2.6.3 - 15/10/2026

**bug Fixes**

- Updated `zircote/swagger-php` (4.x-dev → 5.8.*): the library was not compatible with PHP 8.4 and above — the `vendor/bin/openapi` command displayed the error `Constant E_STRICT is deprecated` and produced an incomplete file

**documentation**

- update [Building an API](build/controller-edu-api.md): removed the workaround note (`generate-openapi.php` script), the `vendor/bin/openapi` command works again with PHP 8.4 and above

## v2.6.2 - 15/10/2026

**documentation**

- new [Mermaid](https://mermaid.js.org){:target="_blank"} support in the documentation: diagrams automatically adapt to the light/dark theme and are only loaded on pages that contain them
- update [Protecting a form (CSRF)](build/csrf.md): the ASCII diagram becomes a Mermaid sequence diagram
- new [What is MVC?](build/concept-mvc.md): flowchart of a request's lifecycle with the actual classes (FastRouteCore, Controller, Repository, TwigCore)
- update [How do I create a controller?](build/controller-edu.md): the static diagram is replaced by a flowchart of HelloController's lifecycle
- new [Building the CRUD](build/dataservice-select.md): class diagram of VilleController / VilleRepository / Ville / DatabaseService
- new [HTTP errors](build/http-error.md): flowchart of the error pipeline with the dev/production branches
- update [How do I create a form?](build/controller-edu-post.md): sequence diagram of the PRG (Post/Redirect/Get) pattern
- new [DatabaseService: data management](build/dataservice.md): class diagram of the connection (mysql, mariadb, sqlite adapters)
- new [The debug bar and the profiler](build/debug-bar.md): flowchart of the flow from the logs to the profiler
- new [Route: generating a URL from a route name](build/route.md): flowchart of URL generation and of the BadRouteException

## v2.6.1 - 15/10/2026

**new features**

- Migration from MkDocs to [ProperDocs](https://github.com/ProperDocs/properdocs){:target="_blank"}, the maintained fork of MkDocs 1.x — MkDocs has not been maintained since August 2024 and its repository is closed to outside contributions. ProperDocs is a drop-in replacement: the Material theme and all plugins remain unchanged, the configuration becomes `properdocs.yml` and the `mkdocs build`/`mkdocs serve` commands become `properdocs build`/`properdocs serve`

**bug Fixes**

- Social cards (og:image) are generated again: the social plugin's logo is resolved through the theme logo (prefixed with the documentation folder)

**documentation**

- new "VILLE" use case: STEP 1 and Refactoring: added to the navigation (content removed since)
- update [Contributing to the docs](contributor/documentation.md): installing and running with ProperDocs
- removed the obsolete `dataservice-insert.md` page (content merged into [Building the CRUD](build/dataservice-select.md))

## v2.6.0 - 14/10/2026

**new features**

- New `CsrfService` service to protect forms against CSRF (Cross-Site Request Forgery) attacks using the "Synchronizer Token" pattern: random token (CSPRNG) scoped by route name, comparison protected against timing attacks (`hash_equals`), token rotation after each successful validation. New Twig function `csrf_token('nom_route')` to generate the form's hidden field and `Request::isCsrfTokenValid('nom_route')` method for validation in the controller (the token can also be sent in the `X-CSRF-TOKEN` HTTP header for AJAX calls). Application demo on the `/medecin/import` route and full documentation in [docs/build/csrf.md](build/csrf.md)

## v2.5.0 - 08/10/2026

**new features**

- [#149](https://github.com/studoo-app/edu-framework/issues/149) New `php bin/edu make:entity` command: generates the entity (`app/Entity`) and its repository (`app/Repository`) — interactive mode or `--fields` option, typed camelCase fluent getters and setters, hydration with casts in the repository @bfoujols
- [#150](https://github.com/studoo-app/edu-framework/issues/150) The framework's identity settings (name, version, date_version, php_version) can be customised via the `eduframe.yml` file at the root of the framework (read from `vendor/` in projects) — no more need to edit `ConfigCore.php`, explicit `ErrorConfigException` if the file is invalid @bfoujols
- [#76](https://github.com/studoo-app/edu-framework/issues/76) Styled HTTP error pages (404, 405, 403, 500): standalone, styled templates in `app/Template/error/`, new `HttpError403Controller`, the `ErrorHttpStatusException` class lets you raise an HTTP error from a controller (Example: 403 forbidden), any unhandled exception displays the 500 page with the exception message in development mode (hidden in production). The `http-Default.html.twig` template becomes `http-500.html.twig`. The framework name is no longer displayed on error pages (title and footer) @bfoujols

**bug Fixes**

- [#76](https://github.com/studoo-app/edu-framework/issues/76) Fixed the debug bar display: global styles of the host page (e.g. `a { margin-top: 1.5rem; }`) no longer break the alignment of the bar's blocks — CSS reset on the toolbar links, which is now immune to the application's stylesheets @bfoujols

**documentation**

- new [The make:entity command](installation/command-edu.md): generating an entity and its repository
- update [Building the CRUD](build/dataservice-select.md): note on generating the files with `make:entity`
- update [Installation](installation/installation.md): the framework settings (eduframe.yml)
- new [HTTP errors](build/http-error.md): 403/404/405/500 pages, customisation, `ErrorHttpStatusException`

> Release notes for v2.5.0
>
> [https://github.com/studoo-app/edu-framework/milestone/22](https://github.com/studoo-app/edu-framework/milestone/22)

  <br>

## v2.4.0 - 08/10/2026

**new features**

- [#147](https://github.com/studoo-app/edu-framework/issues/147) A controller can now handle several routes through several methods: the `controller` key of the "app/Config/routes.yaml" file accepts the `Controller\VilleController::index` syntax. Without an explicit method, the `execute()` method is called as before (100% backward compatible) @bfoujols
- The called method is validated by the framework (public, non-static, Request-typed parameter, string|null return type) and throws an `ErrorControllerException` with an explicit message in case of error @bfoujols
- [#75](https://github.com/studoo-app/edu-framework/issues/75) Handling of uploaded files (`$_FILES`) in `Request`: automatic normalisation of the structure (single field and multi-file), `hasFile()`, `getFile()`, `getFiles()`, `isValid()`, `getExtension()` and `move()` methods, application demo `/medecin/import` @bfoujols
- [#64](https://github.com/studoo-app/edu-framework/issues/64) TWIG cache enabled: compiled templates are stored in `var/cache/twig` (new `cache_path` configuration key), modified templates are recompiled automatically (`auto_reload`) @bfoujols
- [#65](https://github.com/studoo-app/edu-framework/issues/65) New `php bin/edu cache:clear` command to delete the application cache (`var/cache` folder) @bfoujols

**bug Fixes**

- [#86](https://github.com/studoo-app/edu-framework/issues/86) The routes configuration file is checked before being read: an `ErrorRouteConfigNotExistException` with an explicit message (expected path, `route_config_path` configuration, case sensitivity on Linux) replaces the Yaml component's fatal error when "routes.yaml" cannot be found @bfoujols

**documentation**

- new [One controller, several routes](build/controller-edu.md): the `Controller\Class::method` syntax in the routes file
- new [File handling (upload)](build/resquet.md): uploading files in Request
- update [The bin/edu command](installation/command-edu.md): `cache:clear` command
- update [Installation](installation/installation.md): error on the routes file and case sensitivity on Linux
- update [Route](build/route.md): note on the `Controller\Class::method` syntax

> Release notes for v2.4.0
>
> [https://github.com/studoo-app/edu-framework/milestone/21](https://github.com/studoo-app/edu-framework/milestone/21)

  <br>

## v2.3.2 - 05/10/2026

**new features**

- Add documentation for building APIs and CRUD operations @bfoujols

**bug Fixes**

- [#143](https://github.com/studoo-app/edu-framework/issues/143) Update the sqlite and log paths to use relative paths @bfoujols
- fix: update the PHP version check to 8.4 and adjust the error messages @bfoujols
- test: replace sha1 with hash('sha256', ...) in FastRouteCoreTest @bfoujols

> Release notes for v2.3.2
>
> [https://github.com/studoo-app/edu-framework/milestone/v2.3.2](https://github.com/studoo-app/edu-framework/milestone/21)

<br>

## v2.3.1 - 05/10/2026

**bug Fixes**

- [#143](https://github.com/studoo-app/edu-framework/issues/143) Update the sqlite and log paths to use relative paths @bfoujols

> Release notes for v2.3.1
>
> [https://github.com/studoo-app/edu-framework/milestone/v2.3.1](https://github.com/studoo-app/edu-framework/milestone/20)

<br>

## v2.3.0 - 05/10/2026

**new features**

- [#136](https://github.com/studoo-app/edu-framework/issues/136) Implement SQLite logging service for server request logs @bfoujols
- [#136](https://github.com/studoo-app/edu-framework/issues/136) Add BufferFormat and BufferToServer classes for log formatting @bfoujols
- [#136](https://github.com/studoo-app/edu-framework/issues/136) Add logs navigation in the debug bar @bfoujols
- [#136](https://github.com/studoo-app/edu-framework/issues/136) Replace in-bar logs panel with a Symfony-style profiler page @bfoujols
- [#136](https://github.com/studoo-app/edu-framework/issues/136) Enhance filtering capabilities and add status family counting @bfoujols
- [#69](https://github.com/studoo-app/edu-framework/issues/69) Add custom listening socket (#69) Thanks @CapelleGab
- [#122](https://github.com/studoo-app/edu-framework/issues/122) Add Manage DB by dbgate @bfoujols
- [#120](https://github.com/studoo-app/edu-framework/issues/120) Add MailPit Service @bfoujols
- [#97](https://github.com/studoo-app/edu-framework/issues/97) Add slash command @bfoujols
- Update PHP minimum version 8.2 -> 8.4 (composer, bin/edu, CI, doc) @bfoujols
- Update dependencies (vlucas/phpdotenv v5.7, nette/php-generator, zircote/swagger-php, symfony/yaml 6.3 -> 6.4) @bfoujols

**bug Fixes**

- [#136](https://github.com/studoo-app/edu-framework/issues/136) Make SQLite logging non-blocking and resilient @bfoujols
- [#136](https://github.com/studoo-app/edu-framework/issues/136) Send proper HTTP status codes from error controllers @bfoujols
- [#136](https://github.com/studoo-app/edu-framework/issues/136) Gate debug routes on SQLite availability to prevent PDOException @bfoujols
- [#136](https://github.com/studoo-app/edu-framework/issues/136) Buffer stream chunks into complete lines to preserve adjacent request logs @bfoujols
- [#130](https://github.com/studoo-app/edu-framework/issues/130) github action E: Package 'netcat' has no installation candidate @bfoujols
- [#129](https://github.com/studoo-app/edu-framework/issues/129) run command taskfile before-commit @bfoujols
- Fix CI failure on profiler page and logs endpoint @bfoujols
- Fix CI failure on debug toolbar (missing semicolon) and deprecated dynamic property @bfoujols
- Fix debug toolbar always showing an empty controller instead of "aucun" (Request::hasHander always true) @bfoujols
- Fix PHP 8.5 deprecation (PDO::MYSQL_ATTR_INIT_COMMAND -> PDO\MYSQL::ATTR_INIT_COMMAND) @bfoujols
- Fix FastRouteCoreTest failures caused by APP_ENV state leaking between tests @bfoujols
- Fix security advisories on symfony/yaml (CVE-2026-45304, CVE-2026-45305, CVE-2026-45133) @bfoujols

**documentation**

- new [Migrating from version 2.2 to version 2.3](migrate/migration-2_2-2_3.md)
- new [The debug bar and the profiler](build/debug-bar.md)
- update [Start/Stop the application](installation/start-application.md): options of the `start` command
- update [How to install the services](installation/start-services.md): MailPit and dbgate
- update [Before you start](installation/prerequis.md): `pdo_sqlite` extension and PHP 8.4 minimum
- update [The project directory structure](build/arborescence.md): fix to the `compose.yaml` file

> Release notes for v2.3.0
>
> [https://github.com/studoo-app/edu-framework/milestone/v2.3.0](https://github.com/studoo-app/edu-framework/milestone/19)

  <br>

## v2.2.1 - 23/08/2024

**new features**

- [#119](https://github.com/studoo-app/edu-framework/issues/119) add Request::getBody() @bfoujols

**bug Fixes**

- [#117](https://github.com/studoo-app/edu-framework/issues/117) Fix class not use in API controller @bfoujols
- Fix Doc install pip package

> Release notes for v2.2.1
>
> [https://github.com/studoo-app/edu-framework/milestone/18](https://github.com/studoo-app/edu-framework/milestone/18)

  <br>

## v2.2.0 - 08/08/2024

**new features**

- [#112](https://github.com/studoo-app/edu-framework/issues/112) Implement OpenAPI @bfoujols

**bug Fixes**

- [#114](https://github.com/studoo-app/edu-framework/issues/114) Update PHP version 8.1 -> 8.2 @bfoujols
- [#116](https://github.com/studoo-app/edu-framework/pull/116) Add Tests for OpenAPI, Command Cli @bfoujols

> Release notes for v2.2.0
>
> [https://github.com/studoo-app/edu-framework/milestone/17](https://github.com/studoo-app/edu-framework/milestone/17)

  <br>

## v2.1.0 - 20/06/2024

**new features**

- [#87](https://github.com/studoo-app/edu-framework/issues/87) Rebuild a URL from a route name with getNameToPath()
- [#39](https://github.com/studoo-app/edu-framework/issues/39) SQLite support
- [#105](https://github.com/studoo-app/edu-framework/issues/105) New documentation [https://studoo-app.github.io/edu-framework](https://studoo-app.github.io/edu-framework)
- [#86](https://github.com/studoo-app/edu-framework/issues/86) Implement codeSpace by GitHub

**bug Fixes**

- [#96](https://github.com/studoo-app/edu-framework/issues/96) Problem installing phpunit 11 @pbentura @RaphaelBensoussan
- [#92](https://github.com/studoo-app/edu-framework/issues/92) Unit test problem on FastRouteCore

> Release notes for v2.1.0
> 
> [https://github.com/studoo-app/edu-framework/milestone/v2.1.0](https://github.com/studoo-app/edu-framework/milestone/12)

  <br>

## v2.0.2 - 24/05/2024

**bug Fixes**

- [#94](https://github.com/studoo-app/edu-framework/issues/94) fix patch root by @bfoujols in <https://github.com/studoo-app/edu-framework/pull/95>

> Release notes for v2.0.2
> 
> [https://github.com/studoo-app/edu-framework/milestone/v2.0.2](https://github.com/studoo-app/edu-framework/milestone/15)

<br>


## v2.0.1 - 15/05/2024

**bug Fixes**

- [#88](https://github.com/studoo-app/edu-framework/issues/88) Fixed the problem with DatabaseService on the command line

> Release notes for v2.0.1 
> 
> [https://github.com/studoo-app/edu-framework/milestone/v2.0.1](https://github.com/studoo-app/edu-framework/milestone/14)

  <br>

## v2.0.0 - 06/04/2024

**new features**

- [#73](https://github.com/studoo-app/edu-framework/issues/73) added the `php bin/edu make:api` command to generate an API controller
- [#70](https://github.com/studoo-app/edu-framework/issues/70) added the `php bin/edu make:command` command to generate a console command
- [#59](https://github.com/studoo-app/edu-framework/issues/59) added a default page on the route

**deprecations**

- [#68](https://github.com/studoo-app/edu-framework/issues/68) Removed the old Docker configurations
- [#72](https://github.com/studoo-app/edu-framework/issues/72) Bootstrap 5.3 Clean

**bug Fixes**

- [#83](https://github.com/studoo-app/edu-framework/issues/83) Docker compose.yaml: version is obsolete
- [#78](https://github.com/studoo-app/edu-framework/issues/78) Debug bar in error pages

**documentation**

- New interface and organisation by chapter
- new [Request: handling HTTP requests](https://studoo-app.github.io/edu-framework/build/resquet/)
- update [How to install EduFrame](https://studoo-app.github.io/edu-framework/build/index.html): New installation per version
- update [How to install the services](https://studoo-app.github.io/edu-framework/installation/index.html): Docker refactoring
- update [Directory structure](https://studoo-app.github.io/edu-framework/installation/index.html): New v2.0 structure
- update [How to create a controller](https://studoo-app.github.io/edu-framework/build/index.html): More detail
- update [How to handle a POST in a controller](https://studoo-app.github.io/edu-framework/build/index.html): More detail

> Release notes for v2.0.0
> 
> [https://github.com/studoo-app/edu-framework/milestone/v2.0.0](https://github.com/studoo-app/edu-framework/milestone/11?closed=1)

  <br>

## v1.2.0 - 25/03/2024

**new features**

- [#66](https://github.com/studoo-app/edu-framework/issues/66) added the `php bin/edu start` command to start the development server
- [#62](https://github.com/studoo-app/edu-framework/issues/62) added the `php bin/edu check:config` command to check the framework configuration
- [#26](https://github.com/studoo-app/edu-framework/issues/26) Implement PHPDebugBar

**bug fixes**

- [#81](https://github.com/studoo-app/edu-framework/issues/81) Problem retrieving dynamic variables passed in the route via {}

> Release notes for v1.2.0 
> 
> [https://github.com/studoo-app/edu-framework/milestone/v1.2.0](https://github.com/studoo-app/edu-framework/milestone/11?closed=1)

  <br>

## v1.1.0 - 03/2024

Beta version of the debug bar with the following features:

- Debug bar in beta in the development environment
- Docker overhaul for installing the services
- Minor bug fixes
    - Docker: fix for the network of the Mydql and PhpMyAdmin services

## v1.0.0 - 2024

Final version of the framework with the following features:

- Controller creation via the `php bin/edu make:controller` command

## v0.6.0 - 2023

Stable version of the framework with the following features:

- Controller creation
- Template creation
- Route creation
- Database setup

## v0.5.0 - 2023

Development versions of the framework with the following features:

- Setup and design of the framework architecture (MVC)
- several POCs of the MVC layers
- Unit tests
- Setup of the framework documentation (Writerside)
- Framework configuration management (DotEnv)
- Dependency management (Composer)
- Service management (Docker)

## v0.1.0 - 2022

Start of the Edu Framework project
