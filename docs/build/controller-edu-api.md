# Comment construire une API ?

!!! warning "Attention"

    Attention : cette feature est disponible à partir de v2.0

Une [API](../annexes/glossaire.md#api){:target="_blank"} permet à d'autres logiciels (application mobile, site web tiers, script...) de consommer les données de votre application.
Là où un controller classique renvoie une page HTML, un controller API renvoie des données au format [JSON](../annexes/glossaire.md#json){:target="_blank"}.

La commande `make:api` reprend le principe de [`make:controller`](controller-edu.md) et génère tout le nécessaire pour exposer une API :

|                       | `make:controller`                     | `make:api`                              |
|-----------------------|---------------------------------------|-----------------------------------------|
| Réponse renvoyée      | HTML (vue Twig)                       | JSON                                    |
| Dossier du controller | `app/Controller/`                     | `app/Controller/api/`                   |
| URI générée           | `/<nom>`                              | `/api/<nom>`                            |
| Vue générée           | `app/Template/<nom>/<nom>.html.twig`  | Aucune (pas de vue)                     |
| Documentation         | Aucune                                | Attributs [OpenAPI](https://swagger.io/specification/){:target="_blank"} générés |

## La commande make:api

Vous pouvez l'utiliser à la racine de votre projet en tapant la commande suivante :

```Shell
php bin/edu make:api NOM_DU_CONTROLLER
```

N'oubliez pas de remplacer **"NOM_DU_CONTROLLER"** par le nom de votre controller. L'argument `controller-name` est obligatoire.

La commande génère **trois éléments** :

| Élément généré        | Emplacement                                   | Détail                                                                                     |
|-----------------------|-----------------------------------------------|--------------------------------------------------------------------------------------------|
| Le controller API     | `app/Controller/api/<Nom>Controller.php`      | Renvoie une réponse au format JSON                                                        |
| La route              | `app/Config/routes.yaml`                      | Nom préfixé par `api_`, URI préfixée par `/api/`, méthode HTTP `GET`                        |
| Le fichier OpenAPI    | `app/Controller/api/openapi.php`              | Généré **uniquement lors du premier** `make:api` (non écrasé ensuite)                       |

## Les règles de nommage

Le nom passé en argument détermine le nom de la classe, le nom de la route et l'URI :

| Argument saisi  | Classe générée         | Nom de la route      | URI générée             |
|-----------------|------------------------|----------------------|--------------------------|
| `hello`         | `HelloController`      | `api_hello`          | `/api/hello`             |
| `ville`         | `VilleController`      | `api_ville`          | `/api/ville`             |
| `MyUserTest`    | `MyUserTestController` | `api_my-user-test`   | `/api/my-user-test`      |

Les règles appliquées par la commande :

- Le nom de la **classe** : première lettre du nom en majuscule + le suffixe `Controller`
- L'**URI** : nom en minuscules, chaque majuscule devient un tiret (format *kebab-case*), avec le préfixe `/api/`
- Le nom de la **route** : `api_` + l'URI sans le `/`

!!! warning "Attention"

    Évitez le camelCase qui commence par une minuscule. La commande `php bin/edu make:api myUserTest` génère bien la classe `MyUserTestController`, mais l'URI produite sera `/api/user-test` (le premier mot est ignoré dans l'URI).
    Préférez un nom tout en minuscules (`myusertest`) ou en PascalCase (`MyUserTest`).

# Exemple de création d'une API

Nous allons créer une API "hello" avec la commande suivante :

```Shell
php bin/edu make:api Hello
```

Voici le résultat de la commande :

```
 ==============================================

  Class : ./app/Controller/api/HelloController
  URI : /api/hello

 ==============================================

 [OK] Controller and route successfully generated
```

Voici l'arborescence des fichiers générés ou modifiés :

``` hl_lines="3 5 6 7"
├── app
│   ├── Config
│   │   └── routes.yaml
│   ├── Controller
│   │   └── api
│   │       ├── HelloController.php
│   │       └── openapi.php
```

### Le fichier HelloController.php

Cette commande va créer un fichier "HelloController.php" dans le dossier "app/Controller/api".

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

Plusieurs points sont à observer dans ce controller :

- Le **namespace** est `Controller\api` : les controllers d'API sont séparés des controllers "web" (namespace `Controller`).
- Comme tout controller, il **implémente l'interface `ControllerInterface`** et sa méthode `execute()` prend en paramètre un objet [Request](../boost/resquet.md) et retourne une chaîne de caractères (string) ou null.
- La fonction native `header('Content-Type: application/json')` indique au client que la réponse est au format JSON.
- La fonction native `json_encode()` transforme un tableau PHP en chaîne JSON.
- Il n'y a **pas de vue Twig** : la chaîne JSON renvoyée par `json_encode()` est directement la réponse.
- Les **attributs OpenAPI** (`#[Attributes\Get(...)]` et `#[Attributes\Response(...)]`) décrivent l'API pour sa documentation. Ils sont détaillés dans la section [Documenter son API avec OpenAPI](#documenter-son-api-avec-openapi).

### Le fichier openapi.php

Le fichier "openapi.php" est créé dans le dossier "app/Controller/api" **seulement lors du premier** `make:api`.
Si le fichier existe déjà, la commande ne le régénère pas.

```php
<?php

namespace Controller\api;

use OpenApi\Attributes;

#[Attributes\Info(title: 'My First API', version: '0.1')]
class openapi
{
}
```

Ce fichier contient les **informations globales de votre API** (titre, version) utilisées par les outils OpenAPI pour générer la documentation complète. Vous pouvez personnaliser le titre et la version :

```diff
- #[Attributes\Info(title: 'My First API', version: '0.1')]
+ #[Attributes\Info(title: 'Mon API Edu Framework', version: '1.0')]
```

### La route dans le fichier routes.yaml

La commande ajoute automatiquement la route dans le fichier "app/Config/routes.yaml" :

```yaml
api_hello:
    uri: /api/hello
    controller: Controller\api\HelloController
    httpMethod: [GET]
```

- Le nom de la route est préfixé par `api_` : `api_hello`
- L'URI est préfixée par `/api/` : `/api/hello`
- Le controller est renseigné avec son namespace complet : `Controller\api\HelloController`
- La méthode HTTP autorisée par défaut est `GET`

!!! info "Réécriture du fichier routes.yaml"

    La commande enregistre la configuration via le composant Yaml de Symfony : le fichier "app/Config/routes.yaml" est réécrit au passage (indentation normalisée, guillemets ajoutés autour des URI contenant des caractères spéciaux comme `/user/{id}`).
    Si le format de vos lignes change légèrement après un `make:api`, c'est normal.

### Tester l'API

Démarrez votre application :

```Shell
php bin/edu start
```

Puis testez votre API :

=== "curl"

    ```Shell
    curl -i http://localhost:8042/api/hello
    ```

    Résultat :

    ```
    HTTP/1.1 200 OK
    Content-Type: application/json

    [{"nom":"Yohaio","prenom":"Benoit"},{"nom":"Toma","prenom":"Yann"}]
    ```

=== "Navigateur"

    Tapez l'URL suivante : [http://localhost:8042/api/hello](http://localhost:8042/api/hello){:target="_blank"}

    Le navigateur affiche la réponse JSON (mise en forme automatique selon votre navigateur) :

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

### Les codes HTTP renvoyés

| Code HTTP | Signification   | Quand ?                                                                    |
|-----------|-----------------|----------------------------------------------------------------------------|
| `200`     | OK              | La requête `GET` a réussi, la réponse contient le JSON                       |
| `404`     | Not Found       | L'URI demandée n'existe pas (faute de frappe, route non générée)             |
| `405`     | Method Not Allowed | La méthode HTTP utilisée n'est pas autorisée par la route (`httpMethod`)  |

Vous pouvez les vérifier facilement avec curl :

```Shell
curl -i http://localhost:8042/api/helloo      # HTTP 404 : la route /api/helloo n'existe pas
curl -i -X POST http://localhost:8042/api/hello   # HTTP 405 : la route n'accepte que la méthode GET
```

!!! info "Pourquoi un code 405 ?"

    Par défaut, la route générée n'autorise que la méthode `GET` (`httpMethod: [GET]`).
    Pour accepter d'autres méthodes, il faut modifier le fichier "app/Config/routes.yaml" (voir l'[exemple n°3](#exemple-a-realiser-n3-creer-une-ville-en-post)).

# Personnaliser les données renvoyées

Le controller généré contient des données d'exemple (`$listTest`). Les sections suivantes proposent **trois exemples à réaliser** pour le remplacer par vos propres données.

## Exemple à réaliser n°1 : renvoyer des villes stockées en base

!!! info "Objectif"

    - Création du fichier de base via la commande `make:api`
    - Remplacer les données d'exemple par les villes issues de la base de données via [DatabaseService](../boost/dataservice.md)
    - Tester l'API avec curl

**Prérequis :**

- Votre base de données doit être démarrée : [Démarrer les services](../installation/start-services.md)
- La table `ville` doit exister dans votre base. Si ce n'est pas le cas, exécutez les requêtes SQL du [use case VILLE](use-case-ville.md#creation-de-la-table) :

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

Créez le controller API avec la commande suivante :

```Shell
php bin/edu make:api Ville
```

Puis modifiez le fichier "app/Controller/api/VilleController.php" pour interroger la base de données :

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
+	#[Attributes\Response(response: '200', description: 'La liste des villes')]
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

Testez votre API :

```Shell
curl -i http://localhost:8042/api/ville
```

Résultat :

```
HTTP/1.1 200 OK
Content-Type: application/json

[{"id":1,"nom":"Paris","code_postal":"75000","nombre_habitant":2200000},{"id":2,"nom":"Marseille","code_postal":"13000","nombre_habitant":800000},{"id":3,"nom":"Lyon","code_postal":"69000","nombre_habitant":500000}]
```

!!! info "Pourquoi `PDO::FETCH_ASSOC` ?"

    Par défaut, la méthode `fetchAll()` renvoie chaque ligne avec **à la fois** les clés numériques (`0`, `1`...) et les clés associatives (`id`, `nom`...) : le JSON serait dupliqué.
    `PDO::FETCH_ASSOC` ne garde que les clés associatives, ce qui produit un JSON propre et lisible.

!!! warning "L'import `use PDO;` est obligatoire"

    Le controller est dans le namespace `Controller\api`. Contrairement aux fonctions, les classes PHP **ne retombent pas** automatiquement dans l'espace global : sans l'import `use PDO;`, l'utilisation de `PDO::FETCH_ASSOC` produirait l'erreur `Class "Controller\api\PDO" not found`.

## Exemple à réaliser n°2 : un paramètre dans l'URI

!!! info "Objectif"

    - Créer une API qui renvoie **une seule ville** à partir de son identifiant : `GET /api/ville/{id}`
    - Récupérer un paramètre de route avec la classe [Request](../boost/resquet.md)
    - Documenter le paramètre avec l'attribut OpenAPI `Parameter`

En REST, il est d'usage d'exposer une ressource unique sous la forme `/api/ville/{id}`.
Nous allons créer un second controller API dédié à ce besoin :

```Shell
php bin/edu make:api VilleDetail
```

La commande génère l'URI `/api/ville-detail`. Nous allons la modifier pour respecter la convention REST.

Modifiez l'URI de la route dans le fichier "app/Config/routes.yaml" :

```diff
api_ville-detail:
-   uri: /api/ville-detail
+   uri: /api/ville/{id}
    controller: Controller\api\VilleDetailController
    httpMethod: [GET]
```

Puis modifiez le fichier "app/Controller/api/VilleDetailController.php" :

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
+	#[Attributes\Response(response: '200', description: 'La ville demandee')]
+	#[Attributes\Response(response: '404', description: 'Ville introuvable')]
	public function execute(Request $request): string|null
	{
		header('Content-Type: application/json');

+		$id = $request->get("id");
+
+		if ($id === null) {
+			http_response_code(404);
+			return json_encode(["erreur" => "Ville introuvable"]);
+		}
+
+		$comBase = DatabaseService::getConnect();
+		$statementPDO = $comBase->prepare("SELECT * FROM ville WHERE id = :id");
+		$statementPDO->execute(["id" => (int) $id]);
+		$ville = $statementPDO->fetch(PDO::FETCH_ASSOC);
+
+		if ($ville === false) {
+			http_response_code(404);
+			return json_encode(["erreur" => "Ville introuvable"]);
+		}
+
+		return json_encode($ville);
	}
}
```

Explications :

- Le paramètre dynamique `{id}` de la route est récupéré avec la méthode `get()` de la classe [Request](../boost/resquet.md), comme dans le [use case VILLE](use-case-ville.md).
- La requête utilise une requête préparée avec le paramètre `:id` pour éviter l'injection SQL.
- La fonction native `http_response_code(404)` permet de renvoyer un code HTTP d'erreur avec un corps JSON explicite.
- L'attribut `#[Attributes\Parameter(...)]` documente le paramètre de chemin pour OpenAPI.

Testez votre API :

```Shell
curl -i http://localhost:8042/api/ville/1     # HTTP 200 : la ville dont l'id est 1
curl -i http://localhost:8042/api/ville/999   # HTTP 404 : la ville 999 n'existe pas
```

Résultat :

```
HTTP/1.1 200 OK
Content-Type: application/json

{"id":1,"nom":"Paris","code_postal":"75000","nombre_habitant":2200000}
```

!!! warning "Attention"

    N'oubliez pas de modifier **aussi** le chemin dans l'attribut `#[Attributes\Get(path: '...')]` du controller (dans le diff ci-dessus) : la documentation OpenAPI doit rester cohérente avec la route réelle déclarée dans "app/Config/routes.yaml".

## Exemple à réaliser n°3 : créer une ville en POST

!!! info "Objectif"

    - Accepter la méthode `POST` sur la route `api_ville`
    - Créer une ville via l'API : `POST /api/ville`
    - Renvoyer le code HTTP `201 Created`

En REST, une même URI peut servir à plusieurs opérations : `GET /api/ville` renvoie la liste, `POST /api/ville` crée une nouvelle ressource.
C'est ce que nous allons mettre en place sur le controller de l'[exemple n°1](#exemple-a-realiser-n1-renvoyer-des-villes-stockees-en-base).

### Modifier la route

Ajoutez la méthode `POST` dans le fichier "app/Config/routes.yaml" :

```diff
api_ville:
    uri: /api/ville
    controller: Controller\api\VilleController
-   httpMethod: [GET]
+   httpMethod: [GET,POST]
```

!!! warning "Attention"

    Dans le cas d'une erreur "HTTP 405 Method Not Allowed", vérifiez que la méthode `POST` est bien ajoutée dans le fichier de configuration des routes.

### Modifier le controller

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
	#[Attributes\Response(response: '200', description: 'La liste des villes')]
+	#[Attributes\Response(response: '201', description: 'La ville creee')]
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

Explications :

- La méthode `getHttpMethod()` de la classe [Request](../boost/resquet.md#gethttpmethod) permet de distinguer le traitement du `GET` (liste) et du `POST` (création).
- Les données envoyées par le client sont récupérées avec la méthode `get()` de la classe [Request](../boost/resquet.md#getkey).
- La fonction native `http_response_code(201)` renvoie le code HTTP `201 Created`, convention REST pour une création réussie.
- L'attribut `#[Attributes\Post(path: '...')]` documente la nouvelle opération pour OpenAPI.

Testez votre API :

```Shell
curl -i -X POST http://localhost:8042/api/ville \
  -d "nom=Bordeaux" \
  -d "code_postal=33000" \
  -d "nombre_habitant=257000"
```

Résultat :

```
HTTP/1.1 201 Created
Content-Type: application/json

{"id":4,"nom":"Bordeaux","code_postal":"33000","nombre_habitant":257000}
```

!!! info "Corps de requête au format JSON"

    La classe [Request](../boost/resquet.md) lit les données de formulaire (type `application/x-www-form-urlencoded`, comme celles envoyées par curl ou un formulaire HTML).
    Si votre client envoie directement un corps JSON (type `application/json`, fréquent avec Postman ou un frontend JavaScript), les données se lisent ainsi :

    ```php
    $data = json_decode(file_get_contents('php://input'), true);
    $nom = $data['nom'] ?? null;
    ```

# Documenter son API avec OpenAPI

[OpenAPI](https://swagger.io/specification/){:target="_blank"} est un format de description des API (anciennement Swagger).
La commande `make:api` génère déjà les attributs nécessaires, répartis dans deux fichiers :

- **"app/Controller/api/openapi.php"** : les informations globales de l'API (attribut `Info` : titre, version)
- **Le controller généré** : les informations de chaque opération (attributs `Get`, `Post`, `Response`, `Parameter`...)

Vous pouvez enrichir la documentation au fil de l'eau, par exemple :

```php
#[Attributes\Get(path: '/api/ville/{id}')]
#[Attributes\Parameter(name: 'id', in: 'path', required: true, schema: new Attributes\Schema(type: 'integer'))]
#[Attributes\Response(response: '200', description: 'La ville demandee')]
#[Attributes\Response(response: '404', description: 'Ville introuvable')]
public function execute(Request $request): string|null
```

Les attributs les plus utiles :

| Attribut                                  | Rôle                                                     |
|-------------------------------------------|----------------------------------------------------------|
| `#[Attributes\Get(path: '...')]`          | Documente une opération `GET` sur le chemin indiqué       |
| `#[Attributes\Post(path: '...')]`         | Documente une opération `POST` sur le chemin indiqué      |
| `#[Attributes\Response(response: '...')]` | Documente un code de réponse HTTP et sa description       |
| `#[Attributes\Parameter(...)]`            | Documente un paramètre (de chemin, de requête...)          |
| `#[Attributes\Info(title: '...', version: '...')]` | Informations globales de l'API (dans "openapi.php") |

### Générer la spécification OpenAPI

La librairie [swagger-php](https://github.com/zircote/swagger-php){:target="_blank"} (installée avec Edu Framework) analyse ces attributs et produit un fichier de spécification :

```Shell
./vendor/bin/openapi app/Controller/api -o public/openapi.yaml
```

Exemple de résultat :

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
                    description: 'La liste des villes'
```

Ce fichier peut ensuite être visualisé dans des outils comme [Swagger Editor](https://editor-next.swagger.io/){:target="_blank"}, ou servi à des outils de génération de client.

!!! bug "Incompatibilité connue avec PHP 8.4+"

    La version de swagger-php actuellement embarquée par Edu Framework (4.x-dev) n'est pas compatible avec PHP 8.4 et supérieur : la commande `vendor/bin/openapi` affiche l'erreur `Constant E_STRICT is deprecated` et produit un fichier incomplet.

    En attendant la mise à jour de cette dépendance, vous pouvez générer la spécification via un petit script PHP :

    ```php
    <?php
    // Par exemple dans un fichier generate-openapi.php à la racine du projet
    require 'vendor/autoload.php';

    $spec = (new OpenApi\Generator())->generate([__DIR__ . '/app/Controller/api']);
    file_put_contents(__DIR__ . '/public/openapi.yaml', $spec->toYaml());
    ```

    ```Shell
    php generate-openapi.php
    ```

    Des messages d'avertissement (deprecated) peuvent s'afficher : ils n'empêchent pas la génération du fichier "public/openapi.yaml".

# Les erreurs possibles de la commande

| Message affiché                                        | Cause                                                                                                       | Solution                                                                                             |
|--------------------------------------------------------|-------------------------------------------------------------------------------------------------------------|------------------------------------------------------------------------------------------------------|
| `Route already exists.`                                | Une route `api_<nom>` existe déjà dans "app/Config/routes.yaml" (la commande a déjà été exécutée avec ce nom) | Choisir un autre nom, ou supprimer / renommer la route dans "app/Config/routes.yaml"                  |
| `Controller already exists.`                           | Le fichier "app/Controller/api/\<Nom\>Controller.php" existe déjà (même si la route a été renommée)          | Choisir un autre nom, ou supprimer le fichier controller                                             |
| `Not enough arguments (missing: "controller-name")`   | L'argument obligatoire n'a pas été passé à la commande                                                       | Relancer la commande : `php bin/edu make:api <nom>`                                                   |

!!! info "Ordre des vérifications"

    La commande vérifie d'abord l'existence de la **route**, puis celle du **controller**.
    Si vous exécutez deux fois `make:api` avec le même nom, c'est donc `Route already exists.` qui s'affiche.

# Pour aller plus loin

- [Request - La gestion des requêtes HTTP](../boost/resquet.md)
- [Route - Générer une URL par le nom](../boost/route.md)
- [DatabaseService - La gestion des données](../boost/dataservice.md)
- [Le use case "VILLE" complet (CRUD)](use-case-ville.md)
- [La documentation de swagger-php](https://github.com/zircote/swagger-php){:target="_blank"}
- [La spécification OpenAPI](https://swagger.io/specification/){:target="_blank"}
