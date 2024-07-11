# Comment faire un formulaire ?

Pour créer un post dans un controller, il faut faire un formulaire HTML dans le fichier twig et un traitement dans le controller.

## Les étapes pour créer un post dans un controller

- Créer un controller
- Créer un formulaire dans le fichier twig
- Créer un traitement dans le controller
- Comprendre comment fonctionne la classe [Request](../boost/resquet.md) pour récupérer les données du formulaire

### Création d'un controller

Nous allons créer un page "ville" avec la commande suivante :

```Shell
php bin/edu make:controller Ville
```

La commande va générer un controller "VilleController.php" dans le dossier "src/Controller" et un fichier "ville.html.twig" dans le dossier "src/Template/ville".
Voici l'arborecence des fichiers générés :

``` hl_lines="5 8 9"
├── app
│   ├── Config
│   │   └── routes.yaml
│   ├── Controller
│   │   └── VilleController.php
│   └── Template
│       ├── base.html.twig
│       └── ville
│           └── ville.html.twig
```

### Modifier le fichier de configuration des routes

Par défaut, les routes sont en méthode GET. Pour pouvoir envoyer des données en POST, il faut modifier la route dans le fichier de configuration des routes.
Selectionner dans le fichier "config/routes.yaml" la route de votre controller et ajouter la méthode POST.

Exemple de la route /ville :

```diff
ville:
  uri: /ville
  controller: Controller\VilleController
-  httpMethod: [GET]
+  httpMethod: [GET,POST]
```

!!! warning "Attention"

    Dans le cas d'une erreur "HTTP 405 Method Not Allowed", vérifiez que la méthode POST est bien ajoutée dans le fichier de configuration des routes.

### Créer un formulaire dans le fichier twig

Créer un formulaire en methode POST dans le fichier twig pour envoyer les données au controller.
Selectionner le ficher "ville.html.twig" dans le dossier "src/Template/ville"

Voici un exemple de formulaire :

```diff
{% extends "base.html.twig" %}

{% block title %}{{ titre }}{% endblock %}

{% block content %}
    <h1>{{ titre }}</h1>
+    {% if add_ville is not null %}
+        <div class="alert alert-success" role="alert">
+            La ville est {{ add_ville }}
+        </div>
+    {% endif %}
+
+    <p>Créer une nouvelle ville</p>
+    <form action="/ville" method="post">
+        <label for="nom_ville">Ville</label>
+        <input type="text" id="nom_ville" name="nom_ville">
+        <input type="submit" value="Envoyer">
+    </form>
{% endblock %}
```

Dans cet exemple, on affiche un formulaire pour créer une nouvelle ville. 

On affiche la ville si elle est différente de null. (condition if dans la syntaxe twig)
L'affichage de la ville se fait par la syntaxe twig `{{ nom_ville }}`.

Je vous invite à regarder la documentation de [Twig](https://twig.symfony.com/doc/3.x/){:target="_blank"} pour plus d'informations sur les formulaires.

### Créer un traitement dans le controller

Créer un traitement dans le controller pour récupérer les données du formulaire.

Exemple de traitement :

```diff
<?php

namespace Controller;

use Studoo\EduFramework\Core\Controller\ControllerInterface;
use Studoo\EduFramework\Core\Controller\Request;
use Studoo\EduFramework\Core\View\TwigCore;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class VilleController implements ControllerInterface
{
	public function execute(Request $request): string|null
	{
		return TwigCore::getEnvironment()->render('ville/ville.html.twig',
		    [
		        "titre"   => 'VilleController',
- 		        "request" => $request
+ 		        "add_ville" => $request->get('nom_ville')
		    ]
		);
	}
}
```

Dans cet exemple, on récupère la ville du formulaire dans le controller avec la méthode `get($key)` de la classe [Request](../boost/resquet.md#getkey).

<video controls>
<source src="../assets/screen-form-simple.mp4" type="video/mp4">
</video>

### En cas de changement de route dans le formulaire

Si vous avez changé la route dans le fichier de configuration des routes, il faut modifier l'action du formulaire dans le fichier twig. Dur dur si vous avez plusieurs routes à gérer.
Pour palier à ce problème, vous pouvez utiliser la fonction `{{ getNameToPath('NOM_ROUTE') }}` de twig pour générer l'url de la route.

Dans l'exemple, la route s'appelle "ville" :

``` hl_lines="5"
hello:
    uri: /hello
    controller: Controller\HelloController
    httpMethod: [GET]
ville:
    uri: /ville
    controller: Controller\VilleController
    httpMethod: [GET, POST]
```

Nous allons implémenter la fonction `getNameToPath('NOM_ROUTE')` dans le fichier "ville.html.twig" et plus précisément dans le formulaire :

```diff
{% extends "base.html.twig" %}

{% block title %}{{ titre }}{% endblock %}

{% block content %}
    <h1>{{ titre }}</h1>
        {% if add_ville is not null %}
            <div class="alert alert-success" role="alert">
                    La ville est {{ add_ville }}
                </div>
        {% endif %}

        <p>Créer une nouvelle ville</p>
--        <form action="/ville" method="post">
++        <form action="{{ getNameToPath('ville') }}" method="post">
            <label for="nom_ville">Ville</label>
            <input type="text" id="nom_ville" name="nom_ville">
            <input type="submit" value="Envoyer">
        </form>
{% endblock %}s
```

<video controls>
<source src="../assets/screen-form-path.mp4" type="video/mp4">
</video>

!!! info "Pour aller plus loin"

    Pour aller plus loin, vous pouvez lire la documentation de la classe [Request](../boost/resquet.md) pour comprendre comment gérer les requêtes HTTP.

