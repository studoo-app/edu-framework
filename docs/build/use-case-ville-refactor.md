## Refactoring

!!! warning "Page en cours de rédaction"

    Cette page est en cours de rédaction. Merci de votre compréhension. :smile:

La phase de refactoring est une étape pour améliorer la lisibilité et la maintenabilité du code ?

!!! danger "Attention"

    Le refactoring est une étape délicate qui peut casser le code. Il est important de tester le code après chaque modification.

### Harmonisation des routes

Pour une meilleure organisation des routes, vous pouvez regrouper les routes par entité.

```diff
++ # Ville routes
villeread:
--  uri: /villeread  
++  uri: /ville
    controller: Controller\VilleReadController
    httpMethod: [GET]
villecreate:
--  uri: /villecreate  
++  uri: /ville/new
    controller: Controller\VilleCreateController
    httpMethod: [GET, POST]
villeupdate:
--  uri: /villeupdate/{id}  
++  uri: '/ville/{id}/update'
    controller: Controller\VilleUpdateController
    httpMethod: [GET, POST]
villedelete:
    uri: '/ville/{id}/delete'
    controller: Controller\VilleDeleteController
    httpMethod: [GET]
```

Et... aussi les appeler par leur nom ! En effet, vous pouvez dynamiquement les appeler par leur nom plutôt
que par leur uri. 

Le nom est affiché dans le fichier `app\Config\routes.yaml`

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
++ use Studoo\EduFramework\Core\Controller\Route;

class VilleCreateController implements ControllerInterface
{
	public function execute(Request $request): string|null
	{   
        if ($request->getHttpMethod() === 'POST') {
            $comBase = DatabaseService::getConnect();
            $statementPDO = $comBase->prepare("INSERT INTO ville (nom, code_postal, nombre_habitant) VALUES (:nom, :code_postal, :nb_habitant)");
            $statementPDO->execute([
                'nom' => $request->get('nom'),
                'code_postal' => $request->get('code_postal'),
                'nb_habitant' => (int) $request->get('nombre_habitant')
            ]);

--            header('Location: /villeread');
++            header('Location: ' . (new Route())->getNameToPath('villeread'));
        }

		return TwigCore::getEnvironment()->render('villecreate/villecreate.html.twig',
		    [
		        "titre"   => 'VilleCreateController',
		        "request" => $request
		    ]
		);
	}
}
```

Dans le fichier `villecreate.html.twig`, vous devez ajouter :

```diff
{% extends "base.html.twig" %}

{% block title %}{{ titre }}{% endblock %}

{% block content %}
<h1>{{ titre }}</h1>

-- <form method="post" action="/villecreate">
++ <form method="post" action="{{ getNameToPath('villecreate') }}">
    <div class="form-group">
        <label for="nom">Ville</label>
        <input type="text" class="form-control" id="nom" name="nom">
    </div>
    <div class="form-group">
        <label for="code_postal">Code postal</label>
        <input type="text" class="form-control" id="code_postal" name="code_postal">
    </div>
        <div class="form-group">
        <label for="nombre_habitant">Nombre d'habitant</label>
        <input type="text" class="form-control" id="nombre_habitant" name="nombre_habitant">
    </div>
    <button type="submit" class="btn btn-primary">Enregistrer</button>
 </form>

{% endblock %}
```

Vous devez maintenant changer les noms dans les fichiers de controller et de vue. A vous de jouer !

> Vérifier en rejouant le CRUD de ville si les routes sont bien configurées.
> {style="warning"}

### Regrouper les controllers

Pour une meilleure organisation des controllers, vous pouvez regrouper les controllers par entité.

```shell
mkdir app/Controller/Ville
```

Voici l'arborecence du projet :

```
├── app
│   ├── Config
│   │   └── routes.yaml
│   ├── Controller
│   │   ├── Ville
│   │   │   ├── VilleReadController.php
│   │   │   ├── VilleCreateController.php
│   │   │   ├── VilleUpdateController.php
│   │   │   └── VilleDeleteController.php
```

Dans ce dossier, vous pouvez déplacer les controllers `VilleReadController.php`, `VilleCreateController.php`, `VilleUpdateController.php` et `VilleDeleteController.php`.

Puis vous devez modifier les namespaces dans les fichiers de controller.

```php
namespace Controller\Ville;
```

!!! warning "Attention"

    Vérifier en rejouant le CRUD de ville si les controllers sont bien organisés.


