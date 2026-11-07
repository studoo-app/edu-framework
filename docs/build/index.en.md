# Build with Edu Framework

This section guides you, step by step, from your first page to a complete application: form, CRUD, API, CLI command and tests. Each step is an opportunity to **learn a concept** that you will later find as-is in a professional framework like Symfony.

The pages are grouped into four themes, each with **what you build** and **the concept you learn**.

<div class="grid cards" markdown>

-   :material-sitemap: **Understand**

    ---

    The MVC architecture concept and the directory structure of an Edu Framework project, before writing a single line.

    [:octicons-arrow-right-24: The foundations](#understand-the-foundations)

-   :material-hammer-wrench: **Build**

    ---

    A page, a protected form, the CRUD of an entity, a JSON API and a CLI command.

    [:octicons-arrow-right-24: Build step by step](#build-step-by-step)

-   :material-test-tube: **Test**

    ---

    Automatically check that your code works correctly with PHPUnit.

    [:octicons-arrow-right-24: Test your application](#test-your-application)

-   :material-book-open-variant: **Go further**

    ---

    The HTTP request, data, routes, errors, the debug bar and the profiler.

    [:octicons-arrow-right-24: Go further](#go-further-with-the-framework)

</div>

## Understand the foundations

**Before you start, you need to understand the following points:**

| Page | What you learn |
|------|----------------|
| [The MVC architecture concept](concept-mvc.md) | Separate the logic (controller), the data (model) and the display (view), around a request / response cycle |
| [The project directory structure](arborescence.md) | Know where the controllers, templates, configuration and public files are located |

## Build step by step

Follow the steps in order: each one builds on the previous one.

| Step | What you build | What you learn |
|------|----------------|----------------|
| 1. [Build a page](controller-edu.md) | A controller, its route and its Twig template | Map a URL to a controller, generate HTML with Twig |
| 2. [Build a form](controller-edu-post.md) | A form submitted with the `POST` method | Read the submitted data with the `Request` object |
| 3. [Protect a form (CSRF)](csrf.md) | A CSRF token in the form | Protect against cross-site request forgery |
| 4. [Build the CRUD](dataservice-select.md) | Listing, adding, updating and deleting a city | Entities, repositories and prepared SQL queries |
| 5. [Build an API](controller-edu-api.md) | A JSON API documented in the OpenAPI format | HTTP status codes, JSON format, API documentation |
| 6. [Build a CLI command](new-command-edu.md) | A new `bin/edu` command | Automate a task from the command line |

## Test your application

| Page | What you learn |
|------|----------------|
| [Test your application (unit tests)](tests-unitaires.md) | Write and run PHPUnit tests, test several cases with a DataProvider, test a repository and a controller |

## Go further with the framework

Once you have got started, these pages detail the building blocks used in the previous steps.

| Page | What you learn | In Symfony |
|------|----------------|------------|
| [Request - Handling HTTP requests](resquet.md) | Read the parameters, the HTTP method, the headers and the uploaded files | HttpFoundation `Request` |
| [DatabaseService - Handling data](dataservice.md) | Connect to a MySQL, MariaDB or SQLite database | Doctrine DBAL |
| [Route - Generate a URL from its name](route.md) | Never hard-code a URL in the code or the templates | `path()`, `generateUrl()` |
| [HTTP errors](http-error.md) | The 403, 404, 405 and 500 codes, and customising the error pages | `HttpException`, error templates |
| [The debug bar and the profiler](debug-bar.md) | Analyse a request, its SQL queries and its performance | Web Debug Toolbar and Profiler |

!!! tip "See all the features"

    The [Features](../features.md) page presents all the building blocks of the framework and their Symfony equivalent.
