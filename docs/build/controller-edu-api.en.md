# How to build an API?

!!! warning "Warning"

    Warning: this feature is available from v2.0

An [API](../annexes/glossaire.md#api){:target="_blank"} allows other software (mobile application, third-party website, script...) to consume the data of your application.
Where a classic controller returns an HTML page, an API controller returns data in [JSON](../annexes/glossaire.md#json){:target="_blank"} format.

The `make:api` command follows the same principle as [`make:controller`](controller-edu.md) and generates everything needed to expose an API:

|                       | `make:controller`                     | `make:api`                              |
|-----------------------|---------------------------------------|-----------------------------------------|
| Returned response     | HTML (Twig view)                      | JSON                                    |
| Controller folder     | `app/Controller/`                     | `app/Controller/api/`                   |
| Generated URI         | `/<name>`                             | `/api/<name>`                           |
| Generated view        | `app/Template/<name>/<name>.html.twig`| None (no view)                          |
| Documentation         | None                                  | Generated [OpenAPI](https://swagger.io/specification/){:target="_blank"} attributes |

## The make:api command

You can use it at the root of your project by typing the following command:

```Shell
php bin/edu make:api NOM_DU_CONTROLLER
```

Don't forget to replace **"NOM_DU_CONTROLLER"** with the name of your controller. The `controller-name` argument is required.

The command generates **three elements**:

| Generated element     | Location                                      | Details                                                                                    |
|-----------------------|-----------------------------------------------|--------------------------------------------------------------------------------------------|
| The API controller    | `app/Controller/api/<Name>Controller.php`     | Returns a response in JSON format                                                          |
| The route             | `app/Config/routes.yaml`                      | Name prefixed with `api_`, URI prefixed with `/api/`, HTTP method `GET`                     |
| The OpenAPI file      | `app/Controller/api/openapi.php`              | Generated **only on the first** `make:api` (not overwritten afterwards)                     |

## Naming rules

The name passed as an argument determines the class name, the route name and the URI:

| Argument entered | Generated class       | Route name           | Generated URI            |
|-----------------|------------------------|----------------------|--------------------------|
| `hello`         | `HelloController`      | `api_hello`          | `/api/hello`             |
| `ville`         | `VilleController`      | `api_ville`          | `/api/ville`             |
| `MyUserTest`    | `MyUserTestController` | `api_my-user-test`   | `/api/my-user-test`      |

The rules applied by the command:

- The **class** name: first letter of the name in uppercase + the `Controller` suffix
- The **URI**: name in lowercase, each uppercase letter becomes a hyphen (*kebab-case* format), with the `/api/` prefix
- The **route** name: `api_` + the URI without the `/`

!!! warning "Warning"

    Avoid camelCase starting with a lowercase letter. The `php bin/edu make:api myUserTest` command does generate the `MyUserTestController` class, but the resulting URI will be `/api/user-test` (the first word is ignored in the URI).
    Prefer a fully lowercase name (`myusertest`) or PascalCase (`MyUserTest`).

# Example of creating an API

We are going to create a "hello" API with the following command:

```Shell
php bin/edu make:api Hello
```

Here is the output of the command:

```
 ==============================================

  Class : ./app/Controller/api/HelloController
  URI : /api/hello

 ==============================================

 [OK] Controller and route successfully generated
```

Here is the tree of the generated or modified files:

``` hl_lines="3 5 6 7"
├── app
│   ├── Config
│   │   └── routes.yaml
│   ├── Controller
│   │   └── api
│   │       ├── HelloController.php
│   │       └── openapi.php
```

### The HelloController.php file

This command creates a "HelloController.php" file in the "app/Controller/api" folder.

```php
<?php

namespace Controller\api;

use OpenApi\Attributes;
use Studoo\EduFramework\Core\Controller\ControllerInterface;
use Studoo\EduFramework\Core\Controller\Request;

class HelloController implements ControllerInterface
{
	#[Attributes\Get(path: '/api/hello')]
	#[Attributes\Response(response: '200', description: 'Mettre une description')]
	public function execute(Request $request): string|null
	{
		header('Content-Type: application/json');

		$listTest = [
			0 => ["nom" => "Yohaio", "prenom" => "Benoit"],
			1 => ["nom" => "Toma", "prenom" => "Yann"]
		];

		return json_encode($listTest);
	}
}
```

Several points are worth noting in this controller:

- The **namespace** is `Controller\api`: API controllers are kept separate from "web" controllers (namespace `Controller`).
- Like any controller, it **implements the `ControllerInterface` interface** and its `execute()` method takes a [Request](resquet.md) object as a parameter and returns a string or null.
- The native function `header('Content-Type: application/json')` tells the client that the response is in JSON format.
- The native function `json_encode()` turns a PHP array into a JSON string.
- There is **no Twig view**: the JSON string returned by `json_encode()` is the response itself.
- The **OpenAPI attributes** (`#[Attributes\Get(...)]` and `#[Attributes\Response(...)]`) describe the API for its documentation. They are detailed in the section [Documenting your API with OpenAPI](#documenting-your-api-with-openapi).

### The openapi.php file

The "openapi.php" file is created in the "app/Controller/api" folder **only on the first** `make:api`.
If the file already exists, the command does not regenerate it.

```php
<?php

namespace Controller\api;

use OpenApi\Attributes;

#[Attributes\Info(title: 'My First API', version: '0.1')]
class openapi
{
}
```

This file contains the **global information of your API** (title, version) used by OpenAPI tools to generate the complete documentation. You can customize the title and the version:

```diff
- #[Attributes\Info(title: 'My First API', version: '0.1')]
+ #[Attributes\Info(title: 'My Edu Framework API', version: '1.0')]
```

### The route in the routes.yaml file

The command automatically adds the route to the "app/Config/routes.yaml" file:

```yaml
api_hello:
    uri: /api/hello
    controller: Controller\api\HelloController
    httpMethod: [GET]
```

- The route name is prefixed with `api_`: `api_hello`
- The URI is prefixed with `/api/`: `/api/hello`
- The controller is specified with its fully qualified namespace: `Controller\api\HelloController`
- The HTTP method allowed by default is `GET`

!!! info "Rewriting of the routes.yaml file"

    The command saves the configuration through Symfony's Yaml component: the "app/Config/routes.yaml" file is rewritten in the process (normalized indentation, quotes added around URIs containing special characters such as `/user/{id}`).
    If the format of your lines changes slightly after a `make:api`, this is normal.

### Testing the API

Start your application:

```Shell
php bin/edu start
```

Then test your API:

=== "curl"

    ```Shell
    curl -i http://localhost:8042/api/hello
    ```

    Result:

    ```
    HTTP/1.1 200 OK
    Content-Type: application/json

    [{"nom":"Yohaio","prenom":"Benoit"},{"nom":"Toma","prenom":"Yann"}]
    ```

=== "Browser"

    Type the following URL: [http://localhost:8042/api/hello](http://localhost:8042/api/hello){:target="_blank"}

    The browser displays the JSON response (automatically formatted depending on your browser):

    ```json
    [
        {
            "nom": "Yohaio",
            "prenom": "Benoit"
        },
        {
            "nom": "Toma",
            "prenom": "Yann"
        }
    ]
    ```

### The returned HTTP codes

| HTTP code | Meaning         | When?                                                                      |
|-----------|-----------------|----------------------------------------------------------------------------|
| `200`     | OK              | The `GET` request succeeded, the response contains the JSON                 |
| `404`     | Not Found       | The requested URI does not exist (typo, route not generated)               |
| `405`     | Method Not Allowed | The HTTP method used is not allowed by the route (`httpMethod`)         |

You can easily check them with curl:

```Shell
curl -i http://localhost:8042/api/helloo      # HTTP 404: the /api/helloo route does not exist
curl -i -X POST http://localhost:8042/api/hello   # HTTP 405: the route only accepts the GET method
```

!!! info "Why a 405 code?"

    By default, the generated route only allows the `GET` method (`httpMethod: [GET]`).
    To accept other methods, you must edit the "app/Config/routes.yaml" file (see [exercise 3](#exercise-3-creating-a-city-with-post)).

# Customizing the returned data

The generated controller contains sample data (`$listTest`). The following sections offer **three exercises** to replace it with your own data.

## Exercise 1: returning cities stored in the database

!!! info "Goal"

    - Create the base file with the `make:api` command
    - Replace the sample data with the cities from the database using [DatabaseService](dataservice.md)
    - Test the API with curl

**Prerequisites:**

- Your database must be running: [Starting the services](../installation/start-services.md)
- The `ville` table must exist in your database. If it does not, run the SQL queries of the [city CRUD](dataservice-select.md#database-schema):

```sql
CREATE TABLE ville (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    code_postal VARCHAR(10) NOT NULL,
    nombre_habitant INT NOT NULL
);
INSERT INTO ville (nom, code_postal, nombre_habitant) VALUES ('Paris', '75000', 2200000);
INSERT INTO ville (nom, code_postal, nombre_habitant) VALUES ('Marseille', '13000', 800000);
INSERT INTO ville (nom, code_postal, nombre_habitant) VALUES ('Lyon', '69000', 500000);
```

Create the API controller with the following command:

```Shell
php bin/edu make:api Ville
```

Then edit the "app/Controller/api/VilleController.php" file to query the database:

```diff
<?php

namespace Controller\api;

use OpenApi\Attributes;
use Studoo\EduFramework\Core\Controller\ControllerInterface;
use Studoo\EduFramework\Core\Controller\Request;
+use Studoo\EduFramework\Core\Service\DatabaseService;
+use PDO;

class VilleController implements ControllerInterface
{
	#[Attributes\Get(path: '/api/ville')]
-	#[Attributes\Response(response: '200', description: 'Mettre une description')]
+	#[Attributes\Response(response: '200', description: 'The list of cities')]
	public function execute(Request $request): string|null
	{
		header('Content-Type: application/json');

-		$listTest = [
-			0 => ["nom" => "Yohaio", "prenom" => "Benoit"],
-			1 => ["nom" => "Toma", "prenom" => "Yann"]
-		];
-
-		return json_encode($listTest);
+		$comBase = DatabaseService::getConnect();
+		$statementPDO = $comBase->query("SELECT * FROM ville");
+		$villes = $statementPDO->fetchAll(PDO::FETCH_ASSOC);
+
+		return json_encode($villes);
	}
}
```

Test your API:

```Shell
curl -i http://localhost:8042/api/ville
```

Result:

```
HTTP/1.1 200 OK
Content-Type: application/json

[{"id":1,"nom":"Paris","code_postal":"75000","nombre_habitant":2200000},{"id":2,"nom":"Marseille","code_postal":"13000","nombre_habitant":800000},{"id":3,"nom":"Lyon","code_postal":"69000","nombre_habitant":500000}]
```

!!! info "Why `PDO::FETCH_ASSOC`?"

    By default, the `fetchAll()` method returns each row with **both** numeric keys (`0`, `1`...) and associative keys (`id`, `nom`...): the JSON would be duplicated.
    `PDO::FETCH_ASSOC` keeps only the associative keys, which produces clean and readable JSON.

!!! warning "The `use PDO;` import is required"

    The controller is in the `Controller\api` namespace. Unlike functions, PHP classes **do not fall back** automatically to the global namespace: without the `use PDO;` import, using `PDO::FETCH_ASSOC` would produce the error `Class "Controller\api\PDO" not found`.

## Exercise 2: a parameter in the URI

!!! info "Goal"

    - Create an API that returns **a single city** from its identifier: `GET /api/ville/{id}`
    - Retrieve a route parameter with the [Request](resquet.md) class
    - Document the parameter with the OpenAPI `Parameter` attribute

In REST, it is customary to expose a single resource in the form `/api/ville/{id}`.
We are going to create a second API controller dedicated to this need:

```Shell
php bin/edu make:api VilleDetail
```

The command generates the URI `/api/ville-detail`. We are going to change it to follow the REST convention.

Change the route's URI in the "app/Config/routes.yaml" file:

```diff
api_ville-detail:
-   uri: /api/ville-detail
+   uri: /api/ville/{id}
    controller: Controller\api\VilleDetailController
    httpMethod: [GET]
```

Then edit the "app/Controller/api/VilleDetailController.php" file:

```diff
<?php

namespace Controller\api;

use OpenApi\Attributes;
use Studoo\EduFramework\Core\Controller\ControllerInterface;
use Studoo\EduFramework\Core\Controller\Request;
+use Studoo\EduFramework\Core\Service\DatabaseService;
+use PDO;

class VilleDetailController implements ControllerInterface
{
-	#[Attributes\Get(path: '/api/ville-detail')]
-	#[Attributes\Response(response: '200', description: 'Mettre une description')]
+	#[Attributes\Get(path: '/api/ville/{id}')]
+	#[Attributes\Parameter(name: 'id', in: 'path', required: true, schema: new Attributes\Schema(type: 'integer'))]
+	#[Attributes\Response(response: '200', description: 'The requested city')]
+	#[Attributes\Response(response: '404', description: 'City not found')]
	public function execute(Request $request): string|null
	{
		header('Content-Type: application/json');

+		$id = $request->get("id");
+
+		if ($id === null) {
+			http_response_code(404);
+			return json_encode(["erreur" => "City not found"]);
+		}
+
+		$comBase = DatabaseService::getConnect();
+		$statementPDO = $comBase->prepare("SELECT * FROM ville WHERE id = :id");
+		$statementPDO->execute(["id" => (int) $id]);
+		$ville = $statementPDO->fetch(PDO::FETCH_ASSOC);
+
+		if ($ville === false) {
+			http_response_code(404);
+			return json_encode(["erreur" => "City not found"]);
+		}
+
+		return json_encode($ville);
	}
}
```

Explanations:

- The route's dynamic `{id}` parameter is retrieved with the `get()` method of the [Request](resquet.md) class, as in the [city CRUD](dataservice-select.md).
- The query uses a prepared statement with the `:id` parameter to prevent SQL injection.
- The native function `http_response_code(404)` makes it possible to return an HTTP error code with an explicit JSON body.
- The `#[Attributes\Parameter(...)]` attribute documents the path parameter for OpenAPI.

Test your API:

```Shell
curl -i http://localhost:8042/api/ville/1     # HTTP 200: the city whose id is 1
curl -i http://localhost:8042/api/ville/999   # HTTP 404: city 999 does not exist
```

Result:

```
HTTP/1.1 200 OK
Content-Type: application/json

{"id":1,"nom":"Paris","code_postal":"75000","nombre_habitant":2200000}
```

!!! warning "Warning"

    Don't forget to **also** change the path in the controller's `#[Attributes\Get(path: '...')]` attribute (in the diff above): the OpenAPI documentation must stay consistent with the actual route declared in "app/Config/routes.yaml".

## Exercise 3: creating a city with POST

!!! info "Goal"

    - Accept the `POST` method on the `api_ville` route
    - Create a city through the API: `POST /api/ville`
    - Return the HTTP code `201 Created`

In REST, the same URI can be used for several operations: `GET /api/ville` returns the list, `POST /api/ville` creates a new resource.
This is what we are going to set up on the controller from [exercise 1](#exercise-1-returning-cities-stored-in-the-database).

### Updating the route

Add the `POST` method in the "app/Config/routes.yaml" file:

```diff
api_ville:
    uri: /api/ville
    controller: Controller\api\VilleController
-   httpMethod: [GET]
+   httpMethod: [GET,POST]
```

!!! warning "Warning"

    In case of an "HTTP 405 Method Not Allowed" error, check that the `POST` method has been added to the route configuration file.

### Updating the controller

```diff
<?php

namespace Controller\api;

use OpenApi\Attributes;
use Studoo\EduFramework\Core\Controller\ControllerInterface;
use Studoo\EduFramework\Core\Controller\Request;
use Studoo\EduFramework\Core\Service\DatabaseService;
+use PDO;

class VilleController implements ControllerInterface
{
	#[Attributes\Get(path: '/api/ville')]
+	#[Attributes\Post(path: '/api/ville')]
	#[Attributes\Response(response: '200', description: 'The list of cities')]
+	#[Attributes\Response(response: '201', description: 'The created city')]
	public function execute(Request $request): string|null
	{
		header('Content-Type: application/json');

+		if ($request->getHttpMethod() === "POST") {
+			$comBase = DatabaseService::getConnect();
+			$statementPDO = $comBase->prepare(
+				"INSERT INTO ville (nom, code_postal, nombre_habitant) VALUES (:nom, :code_postal, :nb_habitant)"
+			);
+			$statementPDO->execute([
+				'nom' => $request->get('nom'),
+				'code_postal' => $request->get('code_postal'),
+				'nb_habitant' => (int) $request->get('nombre_habitant')
+			]);
+
+			http_response_code(201);
+			return json_encode([
+				"id" => (int) $comBase->lastInsertId(),
+				"nom" => $request->get('nom'),
+				"code_postal" => $request->get('code_postal'),
+				"nombre_habitant" => (int) $request->get('nombre_habitant')
+			]);
+		}

		$comBase = DatabaseService::getConnect();
		$statementPDO = $comBase->query("SELECT * FROM ville");
		$villes = $statementPDO->fetchAll(PDO::FETCH_ASSOC);

		return json_encode($villes);
	}
}
```

Explanations:

- The `getHttpMethod()` method of the [Request](resquet.md#gethttpmethod) class makes it possible to tell the handling of `GET` (list) apart from `POST` (creation).
- The data sent by the client is retrieved with the `get()` method of the [Request](resquet.md#getkey) class.
- The native function `http_response_code(201)` returns the HTTP code `201 Created`, the REST convention for a successful creation.
- The `#[Attributes\Post(path: '...')]` attribute documents the new operation for OpenAPI.

Test your API:

```Shell
curl -i -X POST http://localhost:8042/api/ville \
  -d "nom=Bordeaux" \
  -d "code_postal=33000" \
  -d "nombre_habitant=257000"
```

Result:

```
HTTP/1.1 201 Created
Content-Type: application/json

{"id":4,"nom":"Bordeaux","code_postal":"33000","nombre_habitant":257000}
```

!!! info "Request body in JSON format"

    The [Request](resquet.md) class reads form data (type `application/x-www-form-urlencoded`, like the data sent by curl or an HTML form).
    If your client sends a JSON body directly (type `application/json`, common with Postman or a JavaScript frontend), the data is read like this:

    ```php
    $data = json_decode(file_get_contents('php://input'), true);
    $nom = $data['nom'] ?? null;
    ```

# Documenting your API with OpenAPI

[OpenAPI](https://swagger.io/specification/){:target="_blank"} is a format for describing APIs (formerly Swagger).
The `make:api` command already generates the required attributes, spread over two files:

- **"app/Controller/api/openapi.php"**: the global information of the API (`Info` attribute: title, version)
- **The generated controller**: the information of each operation (`Get`, `Post`, `Response`, `Parameter`... attributes)

You can enrich the documentation as you go, for example:

```php
#[Attributes\Get(path: '/api/ville/{id}')]
#[Attributes\Parameter(name: 'id', in: 'path', required: true, schema: new Attributes\Schema(type: 'integer'))]
#[Attributes\Response(response: '200', description: 'The requested city')]
#[Attributes\Response(response: '404', description: 'City not found')]
public function execute(Request $request): string|null
```

The most useful attributes:

| Attribute                                 | Role                                                     |
|-------------------------------------------|----------------------------------------------------------|
| `#[Attributes\Get(path: '...')]`          | Documents a `GET` operation on the given path             |
| `#[Attributes\Post(path: '...')]`         | Documents a `POST` operation on the given path            |
| `#[Attributes\Response(response: '...')]` | Documents an HTTP response code and its description       |
| `#[Attributes\Parameter(...)]`            | Documents a parameter (path, query...)                     |
| `#[Attributes\Info(title: '...', version: '...')]` | Global information of the API (in "openapi.php") |

### Generating the OpenAPI specification

The [swagger-php](https://github.com/zircote/swagger-php){:target="_blank"} library (installed with Edu Framework) analyzes these attributes and produces a specification file:

```Shell
./vendor/bin/openapi app/Controller/api -o public/openapi.yaml
```

Example output:

```yaml
openapi: 3.0.0
info:
    title: 'My First API'
    version: '0.1'
paths:
    /api/hello:
        get:
            responses:
                '200':
                    description: 'Mettre une description'
    /api/ville:
        get:
            responses:
                '200':
                    description: 'The list of cities'
```

This file can then be viewed in tools such as [Swagger Editor](https://editor-next.swagger.io/){:target="_blank"}, or fed to client generation tools.

# Possible errors of the command

| Displayed message                                      | Cause                                                                                                       | Solution                                                                                             |
|--------------------------------------------------------|-------------------------------------------------------------------------------------------------------------|------------------------------------------------------------------------------------------------------|
| `Route already exists.`                                | An `api_<name>` route already exists in "app/Config/routes.yaml" (the command has already been run with this name) | Choose another name, or delete / rename the route in "app/Config/routes.yaml"                  |
| `Controller already exists.`                           | The "app/Controller/api/\<Name\>Controller.php" file already exists (even if the route has been renamed)     | Choose another name, or delete the controller file                                                   |
| `Not enough arguments (missing: "controller-name")`   | The required argument was not passed to the command                                                          | Run the command again: `php bin/edu make:api <name>`                                                  |

!!! info "Order of the checks"

    The command first checks whether the **route** exists, then whether the **controller** exists.
    If you run `make:api` twice with the same name, it is therefore `Route already exists.` that is displayed.

# Going further

- [Request - Handling HTTP requests](resquet.md)
- [Route - Generating a URL from its name](route.md)
- [DatabaseService - Data management](dataservice.md)
- [The complete "VILLE" (city) CRUD](dataservice-select.md)
- [The swagger-php documentation](https://github.com/zircote/swagger-php){:target="_blank"}
- [The OpenAPI specification](https://swagger.io/specification/){:target="_blank"}
