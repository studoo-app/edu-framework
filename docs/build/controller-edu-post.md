# Comment faire un post dans un controller ?

Pour créer un post dans un controller, il faut faire un formulaire dans le fichier twig et un traitement dans le controller.

## Les étapes pour créer un post dans un controller

- Modifier le fichire de configuration des routes "config/routes.yaml"
- Créer un formulaire dans le fichier twig
- Créer un traitement dans le controller
- Comprendre comment fonctionne la classe [Request](../boost/resquet.md) pour récupérer les données du formulaire

### Modifier le fichier de configuration des routes

Selectionner dans le fichier "config/routes.yaml" la route de votre controller et ajouter la méthode POST.

Exemple de la route /hello :

```diff
hello:
  uri: /hello
  controller: Controller\TestControllerController
-  httpMethod: [GET]
+  httpMethod: [GET,POST]
```

!!! warning "Attention"

    Dans le cas d'une erreur "405 Method Not Allowed", vérifiez que la méthode POST est bien ajoutée dans le fichier de configuration des routes.

### Créer un formulaire dans le fichier twig

Créer un formulaire en methode POST dans le fichier twig pour envoyer les données au controller.

Exemple de formulaire :

```diff
{% extends "base.html.twig" %}

{% block title %}{{ titre }}{% endblock %}

{% block content %}
    <h1>{{ titre }}</h1>
+    {% if ville is not null %}
+        <div class="alert alert-success" role="alert">
+            La ville est {{ ville }}
+        </div>
+    {% endif %}
+
+    <p>Créer une nouvelle ville</p>
+    <form action="/hello" method="post">
+        <label for="ville">Ville</label>
+        <input type="text" id="ville" name="ville">
+        <input type="submit" value="Envoyer">
+    </form>
{% endblock %}
```

Dans cet exemple, on affiche un formulaire pour créer une nouvelle ville. 

On récupère la ville dans le controller pour l'afficher.
Et on affiche la ville si elle est différente de null. (condition if dans la syntaxe twig)
L'affichage de la ville se fait par la syntaxe twig `{{ ville }}`.

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

class HelloController implements ControllerInterface
{
	public function execute(Request $request): string|null
	{
		return TwigCore::getEnvironment()->render('hello/hello.html.twig',
		    [
		        "titre"   => 'HelloController',
- 		        "request" => $request
+ 		        "ville" => $request->get('ville')
		    ]
		);
	}
}
```

Dans cet exemple, on récupère la ville du formulaire dans le controller avec la méthode get de la classe [Request::get($key)](../boost/resquet.md#getkey).

!!! info "Pour aller plus loin"

    Pour aller plus loin, vous pouvez lire la documentation de la classe [Request](../boost/resquet.md) pour comprendre comment gérer les requêtes HTTP.

