# Declaring a route with the #[Route] attribute

Since version **v2.13.0**, a route can be declared **directly on the controller method** with the PHP `#[Route]` attribute, instead of an entry in the `app/Config/routes.yaml` file.

This is the style of mainstream frameworks (Symfony, Laravel): the route is written next to the code it calls.

## A first example

```php
<?php

namespace Controller;

use Studoo\EduFramework\Core\Controller\Request;
use Studoo\EduFramework\Core\View\TwigCore;
use Studoo\EduRoute\Attribute\Route;

class MedecinController
{
    #[Route('/medecin', name: 'medecin')]
    public function index(Request $request): string|null
    {
        return TwigCore::getEnvironment()->render('medecin/index.html.twig');
    }

    #[Route('/medecin/{id:\d+}/update', methods: ['GET', 'POST'], name: 'medecin_update')]
    public function update(Request $request): string|null
    {
        $id = $request->get('id');
        // ...
    }
}
```

There is nothing else to do: the framework reads the attributes of every controller in the `app/Controller` folder (subfolders included) on each request.

| `#[Route]` parameter | Role                                                         | Equivalent in `routes.yaml` |
|----------------------|--------------------------------------------------------------|-----------------------------|
| first argument       | the URI, with the same syntax (`{id}`, `{id:\d+}`, `[/{name}]`) | `uri`                     |
| `methods`            | HTTP method(s), `GET` by default                             | `httpMethod`                |
| `name`               | route name, used by `getNameToPath()`                        | the route key               |

The called method is the one that carries the attribute: it must be **public**, take a `Request` parameter and return a string (like a `Controller\MedecinController::index` route in `routes.yaml`, see [One controller, several routes](controller-edu.md#un-controller-plusieurs-routes-plusieurs-methodes)).

!!! tip "Several routes for one method"

    The attribute is repeatable: a method can carry several `#[Route]`.

## A common prefix with #[RouteGroup]

The `#[RouteGroup]` attribute, placed on the **class**, adds a prefix to the URI and the name of all its routes:

```php
use Studoo\EduRoute\Attribute\Route;
use Studoo\EduRoute\Attribute\RouteGroup;

#[RouteGroup('/admin', namePrefix: 'admin_')]
class ProductController
{
    #[Route('/product/{id:\d+}', name: 'product')]   // URI /admin/product/{id}, name admin_product
    public function edit(Request $request): string|null
    {
        // ...
    }
}
```

## routes.yaml and attributes together

Both ways of declaring routes work together, so you can migrate one route at a time:

1. the routes of the `app/Config/routes.yaml` file are loaded first;
2. then the `#[Route]` attributes, controller by controller in alphabetical file order.

The `routes.yaml` file becomes optional as soon as a controller declares a route with an attribute.

!!! warning "A unique route name"

    The same name cannot be used both in `routes.yaml` and in an attribute: the framework displays an error giving the route name and the controller method.

## Generating the URL and listing the routes

Routes declared with an attribute work like the others:

- `getNameToPath()` generates their URL from their name (see [Route - Generate a URL from its name](route.md)):

    ```twig
    <a href="{{ getNameToPath('medecin_update', {'id': medecin.id}) }}">Edit</a>
    ```

- the `php bin/edu routes:list` command displays them with the called controller and method (see [The bin/edu command](../installation/command-edu.md#the-routeslist-command)).

## Reported errors

A declaration error is reported as soon as the routes are loaded (error page in `dev` mode, and `routes:list` command), with the controller and method name (messages are in French). Examples:

| Cause                                                 | Example message                                          |
|-------------------------------------------------------|----------------------------------------------------------|
| `#[Route]` on a private or static method              | `La méthode …::index() porte #[Route] mais doit être publique, non statique…` |
| `#[Route]` on the class                               | `#[Route] se place sur une méthode, pas sur la classe…`  |
| A URI already used for the same HTTP method           | `Cannot register two routes matching "/hello" for method "GET" (route déclarée sur …::…())` |
| A name already used                                   | `Cannot register two routes under the name "hello" (route déclarée sur …::…())` |

## Configuration

The folder read and its namespace are set in `public/index.php`, like the framework's other paths:

| Configuration key      | Default value                                 |
|------------------------|-----------------------------------------------|
| `controller_path`      | the project's `app/Controller/` (`base_path`) |
| `controller_namespace` | `Controller\`                                 |

!!! info "Performance"

    Attributes are read through reflection on each request. For an application with a few dozen routes, the cost is negligible.
