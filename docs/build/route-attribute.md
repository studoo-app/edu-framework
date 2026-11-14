# Déclarer une route avec l'attribut #[Route]

À partir de la version **v2.13.0**, une route peut être déclarée **directement sur la méthode du controller** avec l'attribut PHP `#[Route]`, au lieu d'une entrée dans le fichier `app/Config/routes.yaml`.

C'est le style des frameworks du marché (Symfony, Laravel) : la route est écrite à côté du code qu'elle appelle.

## Un premier exemple

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

Il n'y a rien d'autre à faire : le framework lit les attributs de tous les controllers du dossier `app/Controller` (sous-dossiers compris) à chaque requête.

| Paramètre de `#[Route]` | Rôle                                                         | Équivalent dans `routes.yaml` |
|-------------------------|--------------------------------------------------------------|-------------------------------|
| premier argument        | l'URI, avec la même syntaxe (`{id}`, `{id:\d+}`, `[/{name}]`) | `uri`                         |
| `methods`               | méthode(s) HTTP, `GET` par défaut                            | `httpMethod`                  |
| `name`                  | nom de la route, utilisé par `getNameToPath()`               | la clé de la route            |

La méthode appelée est celle qui porte l'attribut : elle doit être **publique**, recevoir un paramètre `Request` et retourner une chaîne de caractères (comme une route `Controller\MedecinController::index` du fichier `routes.yaml`, voir [Un controller, plusieurs routes](controller-edu.md#un-controller-plusieurs-routes-plusieurs-methodes)).

!!! tip "Plusieurs routes pour une même méthode"

    L'attribut est répétable : une méthode peut porter plusieurs `#[Route]`.

## Un préfixe commun avec #[RouteGroup]

L'attribut `#[RouteGroup]`, placé sur la **classe**, ajoute un préfixe à l'URI et au nom de toutes ses routes :

```php
use Studoo\EduRoute\Attribute\Route;
use Studoo\EduRoute\Attribute\RouteGroup;

#[RouteGroup('/admin', namePrefix: 'admin_')]
class ProductController
{
    #[Route('/product/{id:\d+}', name: 'product')]   // URI /admin/product/{id}, nom admin_product
    public function edit(Request $request): string|null
    {
        // ...
    }
}
```

## routes.yaml et attributs ensemble

Les deux modes de déclaration fonctionnent ensemble, vous pouvez migrer une route après l'autre :

1. les routes du fichier `app/Config/routes.yaml` sont chargées en premier ;
2. puis celles des attributs `#[Route]`, controller par controller dans l'ordre alphabétique des fichiers.

Le fichier `routes.yaml` devient facultatif dès qu'un controller déclare une route par attribut.

!!! warning "Un nom de route unique"

    Un même nom ne peut pas être utilisé dans `routes.yaml` et dans un attribut : le framework affiche une erreur qui donne le nom de la route et la méthode du controller.

## Générer l'URL et lister les routes

Les routes déclarées par attribut fonctionnent comme les autres :

- `getNameToPath()` génère leur URL à partir de leur nom (voir [Route - Générer une URL par le nom](route.md)) :

    ```twig
    <a href="{{ getNameToPath('medecin_update', {'id': medecin.id}) }}">Modifier</a>
    ```

- la commande `php bin/edu routes:list` les affiche avec le controller et la méthode appelés (voir [La commande bin/edu](../installation/command-edu.md#la-commande-routeslist)).

## Les erreurs signalées

Une erreur de déclaration est signalée dès le chargement des routes (page d'erreur en mode `dev`, et commande `routes:list`), avec le nom du controller et de la méthode. Exemples :

| Cause                                                       | Exemple de message                                       |
|-------------------------------------------------------------|----------------------------------------------------------|
| `#[Route]` sur une méthode privée ou statique               | `La méthode …::index() porte #[Route] mais doit être publique, non statique…` |
| `#[Route]` sur la classe                                    | `#[Route] se place sur une méthode, pas sur la classe…`  |
| Une URI déjà utilisée pour la même méthode HTTP             | `Cannot register two routes matching "/hello" for method "GET" (route déclarée sur …::…())` |
| Un nom déjà utilisé                                         | `Cannot register two routes under the name "hello" (route déclarée sur …::…())` |

## Configuration

Le dossier lu et son namespace se règlent dans `public/index.php`, comme les autres chemins du framework :

| Clé de configuration   | Valeur par défaut                        |
|------------------------|------------------------------------------|
| `controller_path`      | `app/Controller/` du projet (`base_path`) |
| `controller_namespace` | `Controller\`                            |

!!! info "Performances"

    Les attributs sont lus par réflexion à chaque requête. Pour une application de quelques dizaines de routes, le coût est négligeable.
