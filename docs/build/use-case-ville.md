# Use case "VILLE" : STEP 1

!!! warning "Page en cours de rédaction"

    Cette page est en cours de rédaction. Merci de votre compréhension. :smile:

Nous allons créer un CRUD pour la gestion des villes.

## Création de la table
 
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

## Création du CRUD

### Liste des Villes (Read)

!!! info "Objectif"

    Les étapes sont :
    
    - Création des fichiers de base via la commande `make:controller`
    - Récupération des données via l'objet [Databaseservice](../boost/dataservice.md)
    - Affichage d'un tableau HTML des données récupérées en base de donnée

La commande `make:crontroller` :

```shell
php bin/edu make:controller villeRead
```

Cette commande va créer un fichier `VilleReadController.php` dans le dossier `app/Controller`, ajouter des lignes dans le fichier `config/routes.yaml` et créer un fichier `villeread.html.twig` dans le dossier `app/Template/villeread`.

Voici l'arborecence du projet :

``` hl_lines="5 8 9"
├── app
│   ├── Config
│   │   └── routes.yaml
│   ├── Controller
│   │   └── VilleReadController.php
│   └── Template
│       ├── base.html.twig
│       └── villeread
│           └── villeread.html.twig
```

Dans le fichier VillereadController.php, vous devez ajouter :

```diff
<?php

namespace Controller;

use Studoo\EduFramework\Core\Controller\ControllerInterface;
use Studoo\EduFramework\Core\Controller\Request;
use Studoo\EduFramework\Core\Service\DatabaseService;
use Studoo\EduFramework\Core\View\TwigCore;

class VilleReadController implements ControllerInterface
{
	public function execute(Request $request): string|null
	{
++        $comBase = DatabaseService::getConnect();
++        $statementPDO = $comBase->query("SELECT * FROM ville");
++        $villes = $statementPDO->fetchAll();

		return TwigCore::getEnvironment()->render('villeread/villeread.html.twig',
		    [
		         "titre"   => 'VilleReadController',
		         "request" => $request,
 ++               "villes" => $villes
		    ]
		);
	}
}
```

Ce code va récupérer la liste des villes dans la base de données et les afficher dans la vue `villeread.html.twig`.


Dans le fichier `villeread.html.twig`, vous devez ajouter :

```diff
{% extends "base.html.twig" %}

{% block title %}{{ titre }}{% endblock %}

{% block content %}
    <h1>{{ titre }}</h1>
++    <table class="table">
++        <thead>
++            <tr>
++                <th scope="col">ID</th>
++                <th scope="col">Nom</th>
++                <th scope="col">Code Postal</th>
++                <th scope="col">Nombre d'habitants</th>
++            </tr>
++        </thead>
++        <tbody>
++            {% for ville in villes %}
++                <tr>
++                    <td>{{ ville.id }}</td>
++                    <td>{{ ville.nom }}</td>
++                    <td>{{ ville.code_postal }}</td>
++                    <td>{{ ville.nombre_habitant }}</td>
++                </tr>
++            {% endfor %}
++        </tbody>
++    </table>
{% endblock %}
```

Ce code va afficher la liste des villes dans un tableau HTML. La syntaxe twig `{{ ville.id }}` permet d'afficher l'identifiant de la ville.
Une boucle `for` permet de parcourir la liste des villes et d'afficher les informations de chaque ville.

!!! info "Syntaxe twig"

    Plus d'informations sur la syntaxe twig : [https://twig.symfony.com/doc/3.x/tags/for.html](https://twig.symfony.com/doc/3.x/tags/for.html){:target="_blank"}


### Ajouter des Villes (Create)

!!! info "Objectif"

    Les étapes sont :
    
    - Création des fichiers de base via la commande `make:controller`
    - Mettre les droits sur la route du controller
    - Création d'un formulaire pour envoyer les données en méthode POST dans le controller
    - Récupération des données dans le controller et les inserer en base de donnée

La commmande `make:controller` :

```shell
php bin/edu make:controller villeCreate
```

Cette commande va créer un fichier `VilleCreateController.php` dans le dossier `app/Controller`, ajouter des lignes dans le fichier `config/routes.yaml` et créer un fichier `villecreate.html.twig` dans le dossier `app/Template/villecreate`.

Voici l'arborecence du projet :

``` hl_lines="5 9 10"
├── app
│   ├── Config
│   │   └── routes.yaml
│   ├── Controller
│   │   ├── VilleCreateController.php
│   │   └── VilleReadController.php
│   └── Template
│       ├── base.html.twig
│       ├── villecreate
│       │   └── villecreate.html.twig
│       └── villeread
│           └── villeread.html.twig
```

Dans le fichier VilleCreateController.php, vous devez ajouter :

```diff
<?php

namespace Controller;

use Studoo\EduFramework\Core\Controller\ControllerInterface;
use Studoo\EduFramework\Core\Controller\Request;
use Studoo\EduFramework\Core\Service\DatabaseService;
use Studoo\EduFramework\Core\View\TwigCore;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class VilleCreateController implements ControllerInterface
{
	public function execute(Request $request): string|null
	{   
++        if ($request->getHttpMethod() === 'POST') {
++            $comBase = DatabaseService::getConnect();
++            $statementPDO = $comBase->prepare("INSERT INTO ville (nom, code_postal, nombre_habitant) VALUES (:nom, :code_postal, :nb_habitant)");
++            $statementPDO->execute([
++                'nom' => $request->get('nom'),
++                'code_postal' => $request->get('code_postal'),
++                'nb_habitant' => (int) $request->get('nombre_habitant')
++            ]);
++
++            header('Location: /villeread');
++        }

		return TwigCore::getEnvironment()->render('villecreate/villecreate.html.twig',
		    [
		        "titre"   => 'VilleCreateController',
		        "request" => $request
		    ]
		);
	}
}
```
Ce code va ajouter une ville dans la base de données si la méthode HTTP est POST. 

Il récupère les données du formulaire via l'objet [Request](../boost/resquet.md) et les insère dans la table `ville` via une requête préparée.
Une fois l'ajout effectué, l'utilisateur est redirigé vers la liste des villes via la fonction header('Location: /villeread').

!!! info "Information"

    Plus d'informations sur la fonction header : [https://www.php.net/manual/fr/function.header.php](https://www.php.net/manual/fr/function.header.php){:target="_blank"}


Dans le fichier `villecreate.html.twig`, vous devez ajouter :

```diff
{% extends "base.html.twig" %}

{% block title %}{{ titre }}{% endblock %}

{% block content %}
<h1>{{ titre }}</h1>

++ <form method="post" action="/villecreate">
++    <div class="form-group">
++        <label for="nom">Ville</label>
++        <input type="text" class="form-control" id="nom" name="nom">
++    </div>
++    <div class="form-group">
++        <label for="code_postal">Code postal</label>
++        <input type="text" class="form-control" id="code_postal" name="code_postal">
++    </div>
++        <div class="form-group">
++        <label for="nombre_habitant">Nombre d'habitant</label>
++        <input type="text" class="form-control" id="nombre_habitant" name="nombre_habitant">
++    </div>
++    <button type="submit" class="btn btn-primary">Enregistrer</button>
++ </form>

{% endblock %}
```
Ce code va afficher un formulaire pour ajouter une ville. 

Il récupère les données du formulaire via la méthode POST et les envoie au controller pour les insérer dans la base de données.

!!! info "Information"

    Plus d'informations sur les formulaires HTML : [https://developer.mozilla.org/fr/docs/Web/HTML/Element/Form](https://developer.mozilla.org/fr/docs/Web/HTML/Element/Form){:target="_blank"}

Une erreur http 405 peut apparaitre si la méthode HTTP est en methode POST. 

Pour résoudre cette erreur de droit au POST, vous devez ajouter la méthode POST dans le fichier de configuration des routes.

```diff
villecreate:
  uri: /villecreate
  controller: Controller\VilleCreateController
--  httpMethod: [GET]
++  httpMethod: [GET,POST]
```

Vous pouvez maintenant ajouter un lien dans la page `villeread.html.twig` pour accéder à la page `villecreate.html.twig`.

```Twig
<a href="/villecreate">Ajouter une ville</a>
```

### Modifier des Villes (Update)

!!! info "Objectif"

    Les étapes sont :
    
    - Création des fichiers de base via la commande `make:controller`
    - Ajouter la route dans la liste des villes
    - Mettre les droits sur la route du controller
    - Récuperer les données dans le controller
    - Création d'un formulaire pour modifier les données en méthode POST dans le controller
    - Récupération des données dans le controller et les inserer en base de donnée

La commmande `make:controller` :

```shell
php bin/edu make:controller villeUpdate
```

Voici l'arborescence du projet :

Pour modifier une ville, nous devons gérer sa modification via son idenfifiant (clé primaire). 

Dans notre use case, l'identifiant est ville.id. 

Notre action est de mettre la route `villeupdate` dans la liste avec comme argument `ville.id`

Aller dans le fichier `template/villeread/villeread.html.twig`

```diff
{% extends "base.html.twig" %}

{% block title %}{{ titre }}{% endblock %}

{% block content %}
<h1>{{ titre }}</h1>
    <table class="table">
        <thead>
        <tr>
            <th scope="col">ID</th>
            <th scope="col">Nom</th>
            <th scope="col">Code Postal</th>
            <th scope="col">Nombre d'habitants</th>
++          <th scope="col">Action</th>
        </tr>
        </thead>
        <tbody>
        {% for ville in villes %}
            <tr>
                <td>{{ ville.id }}</td>
                <td>{{ ville.nom }}</td>
                <td>{{ ville.code_postal }}</td>
                <td>{{ ville.nombre_habitant }}</td>
++              <td><a href="/villeupdate/{{ ville.id }}">UPDATE</a></td>
            </tr>
        {% endfor %}
        </tbody>
    </table>
{% endblock %}
```

Dans ce code, on ajoute une colonne `Action` à notre tableau et un lien avec URL `/villeupdate/` en passant `ville.id` en argument GET.
Un nouveau format de route se crée dans le fichier `config/routes.yaml` pour la route `villeupdate`.
`{id}` est un paramètre dynamique inclut dans la route qui permet de récupérer l'identifiant de la ville à modifier.

Vous devez ajouter la route `villeupdate` dans le fichier `config/routes.yaml`.

```diff
villeupdate:
--  uri: /villeupdate  
++  uri: /villeupdate/{id}
    controller: Controller\VilleUpdateController
--  httpMethod: [GET]    
++  httpMethod: [GET,POST]
```
!!! info "Information"

    Ce nouveau format de route est documenté dans la documentation la librairie [FastRoute](https://github.com/nikic/FastRoute){:target="_blank"}.


Nous allons maintenant créer le controller `VilleUpdateController.php` dans le dossier `app/Controller`.

```diff
<?php

namespace Controller;

use Studoo\EduFramework\Core\Controller\ControllerInterface;
use Studoo\EduFramework\Core\Controller\Request;
use Studoo\EduFramework\Core\View\TwigCore;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
++ use Studoo\EduFramework\Core\Service\DatabaseService;

class VilleUpdateController implements ControllerInterface
{
	public function execute(Request $request): string|null
	{
++		$comBase = DatabaseService::getConnect();
++
++		if ($request->getHttpMethod() === 'GET' && $request->get("id") !== null) {
++			$statementPDO = $comBase->prepare("SELECT * FROM ville WHERE id = :id");
++			$statementPDO->execute(['id' => (int) $request->get("id")]);
++			$ville = $statementPDO->fetch();
++		}

		return TwigCore::getEnvironment()->render('villeupdate/villeupdate.html.twig',
		    [
		        "titre"   => 'VilleUpdateController',
--              "request" => $request		        
++		        "ville" => $ville
		    ]
		);
	}
}
```

Ce code va récupérer les informations de la ville à modifier via son identifiant. 

Il va afficher ces informations dans le formulaire de modification via la variable `$ville`.

Nous allons maintenant créer la vue `villeupdate.html.twig` dans le dossier `app/Template/villeupdate`.

```diff
{% extends "base.html.twig" %}

{% block title %}{{ titre }}{% endblock %}

{% block content %}
<h1>{{ titre }}</h1>

++    {% if ville %}
++         <form method="post" action="/villeupdate/{{ ville.id }}">
++            <div class="form-group">
++                <label for="nom">Ville</label>
++                <input type="text" class="form-control" id="nom" name="nom" value="{{ ville.nom }}">
++            </div>
++            <div class="form-group">
++                <label for="code_postal">Code postal</label>
++                <input type="text" class="form-control" id="code_postal" name="code_postal" value="{{ ville.code_postal }}">
++            </div>
++                <div class="form-group">
++                <label for="nombre_habitant">Nombre d'habitant</label>
++                <input type="text" class="form-control" id="nombre_habitant" name="nombre_habitant" value="{{ ville.nombre_habitant }}">
++            </div>
++            <button type="submit" class="btn btn-primary">Enregistrer</button>
++        </form>
++    {% else %}
++        <p>La ville n'existe pas.</p>
++    {% endif %}
{% endblock %}
```

Ce code va afficher un formulaire pré-rempli avec les informations de la ville à modifier. 

Il récupère les données du formulaire via la méthode POST et les envoie au controller pour les mettre à jour dans la base de données.
Dans le formulaire, on observe que l'attribut `action` est `/villeupdate/{{ ville.id }}`. Ce qui s'initialise avec l'identifiant de la ville à modifier.
Si la ville n'existe pas, un message d'erreur est affiché.

Nous allons maintenant persister les données dans la base de données via le controller `VilleUpdateController.php`.

```diff
<?php

namespace Controller;

use Studoo\EduFramework\Core\Controller\ControllerInterface;
use Studoo\EduFramework\Core\Controller\Request;
use Studoo\EduFramework\Core\Service\DatabaseService;
use Studoo\EduFramework\Core\View\TwigCore;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class VilleUpdateController implements ControllerInterface
{
	public function execute(Request $request): string|null
	{
		$comBase = DatabaseService::getConnect();

		if ($request->getHttpMethod() === 'GET' && $request->get("id") !== null) {
			$statementPDO = $comBase->prepare("SELECT * FROM ville WHERE id = :id");
			$statementPDO->execute(['id' => (int) $request->get("id")]);
			$ville = $statementPDO->fetch();
		}

++		if ($request->getHttpMethod() === 'POST') {
++			$statementPDO = $comBase->prepare("UPDATE ville SET nom = :nom, code_postal = :code_postal, nombre_habitant = :nb_habitant WHERE id = :id");
++			$statementPDO->execute([
++				'id' => (int) $request->get('id'),
++              'nom' => $request->get('nom'),
++              'code_postal' => $request->get('code_postal'),
++              'nb_habitant' => (int) $request->get('nombre_habitant')
++			]);
++			header('Location: /villeread');
++		}

		return TwigCore::getEnvironment()->render('villeupdate/villeupdate.html.twig',
		    [
		        "titre"   => 'VilleUpdateController',
		        "ville" => $ville
		    ]
		);
	}
}
```

Ce code va mettre à jour les informations de la ville dans la base de données si la méthode HTTP est POST.

### Supprimer des Villes (Delete)

!!! info "Objectif"

    Les étapes sont :
    - Création des fichiers de base via la commande `make:controller`
    - Ajouter la route dans la liste des villes
    - Faire la requête de suppression dans le controller
    - Rediriger l'utilisateur vers la liste des villes


La commmande `make:controller` :

```shell
php bin/edu make:controller villeDelete
```

Pour supprimer une ville, nous devons gérer sa suppression via son idenfifiant (clé primaire). 

Dans notre use case, l'identifiant est ville.id. 

Notre action est de mettre la route `villedelete` dans la liste avec comme argument `ville.id`

Aller dans le fichier `template/villeread/villeread.html.twig`

```diff 
{% extends "base.html.twig" %}

{% block title %}{{ titre }}{% endblock %}

{% block content %}
<h1>{{ titre }}</h1>
<a href="/villecreate">Ajouter une ville</a>
    <table class="table">
        <thead>
        <tr>
            <th scope="col">ID</th>
            <th scope="col">Nom</th>
            <th scope="col">Code Postal</th>
            <th scope="col">Nombre d'habitants</th>
            <th scope="col">Action</th>
        </tr>
        </thead>
        <tbody>
        {% for ville in villes %}
            <tr>
                <td>{{ ville.id }}</td>
                <td>{{ ville.nom }}</td>
                <td>{{ ville.code_postal }}</td>
                <td>{{ ville.nombre_habitant }}</td>
                <td>
                    <a href="/villeupdate/{{ ville.id }}">UPDATE</a>
++                  <a href="/villedelete/{{ ville.id }}">DELETE</a>
                </td>
            </tr>
        {% endfor %}
        </tbody>
    </table>
{% endblock %}
```

Dans ce code, on ajoute une colonne `Action` à notre tableau et un lien avec URL `/villedelete/` en passant `ville.id` en argument GET.

Vous devez ajouter la route `villedelete` dans le fichier `config/routes.yaml`.

```diff
villedelete:
--  uri: /villedelete
++  uri: /villedelete/{id}
    controller: Controller\VilleDeleteController
    httpMethod: [GET]
```

Nous allons maintenant créer le controller `VilleDeleteController.php` dans le dossier `app/Controller`.

```diff
<?php

namespace Controller;

use Studoo\EduFramework\Core\Controller\ControllerInterface;
use Studoo\EduFramework\Core\Controller\Request;
use Studoo\EduFramework\Core\View\TwigCore;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
++ use Studoo\EduFramework\Core\Service\DatabaseService;

class VilleDeleteController implements ControllerInterface
{
	public function execute(Request $request): string|null
	{
++		if ($request->getHttpMethod() === 'GET' && $request->get("id") !== null) {
++			$comBase = DatabaseService::getConnect();
++			$statementPDO = $comBase->prepare("DELETE FROM ville WHERE id = :id");
++			$statementPDO->execute(['id' => (int) $request->get('id')]);
++		}
++      header('Location: /villeread');
++      return null;

--		return TwigCore::getEnvironment()->render('villedelete/villedelete.html.twig',
--		    [
--		        "titre"   => 'VilleDeleteController',
-- 		        "request" => $request
--		    ]
--		);
	}
}
```

Ce code va supprimer la ville de la base de données via son identifiant. 

Une fois la suppression effectuée, l'utilisateur est redirigé vers la liste des villes via la fonction header('Location: /villeread').
La fonction `return null` permet de ne pas afficher de vue.

Comme il n'y a pas de vue à afficher, vous pouvez supprimer le code de la vue `villedelete.html.twig`.

## Conclusion

Le CRUD est un ensemble d'opérations de base pour la gestion des données dans une base de données. Nous pouvons créer, lire, mettre à jour et supprimer des enregistrements.

Nous l'avons implémenté sans nous soucier des bonnes pratiques de développement.

- Nous avons utilisé des requêtes SQL directes dans la couche controller.
- Les routes ne sont pas organisées pour une meilleure lisibilité.
- Les vues ne sont pas structurées pour une meilleure maintenabilité.

La STEP 2 va nous permettre de refactorer notre code pour améliorer la lisibilité et la maintenabilité du code. :smile:
