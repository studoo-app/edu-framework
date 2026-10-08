# Les erreurs HTTP

Le framework affiche une page d'erreur mise en forme pour les principales erreurs HTTP (à partir de la version v2.5.0).

| Code | Page                       | Quand survient-elle ?                                    |
|------|----------------------------|----------------------------------------------------------|
| 404  | Page introuvable           | La route demandée n'existe pas                           |
| 405  | Méthode non autorisée      | La méthode HTTP n'est pas autorisée (Exemple : POST au lieu de GET) |
| 403  | Accès interdit              | Le controller lève une `ErrorHttpStatusException` avec le code 403 |
| 500  | Erreur interne du serveur  | Une exception non gérée est survenue pendant le traitement |

Chaque page affiche le code de l'erreur, un message explicatif, le message de l'exception **en mode développement uniquement** et un lien de retour à l'accueil.

## Personnaliser les pages d'erreur

Les pages d'erreur sont des templates Twig situés dans le dossier `app/Template/error/` de **votre projet** :

```
├── app
│   └── Template
│       └── error
│           ├── http-403.html.twig
│           ├── http-404.html.twig
│           ├── http-405.html.twig
│           └── http-500.html.twig
```

Vous pouvez modifier leur contenu (texte, style, structure) comme n'importe quel template : elles reçoivent trois variables :

| Variable            | Description                                              |
|---------------------|----------------------------------------------------------|
| `code`              | Le code HTTP de l'erreur (Exemple : 404)                |
| `name`              | Le nom du framework                                      |
| `exception_message` | Le message de l'exception (renseigné uniquement en mode dev) |

!!! info "Pages autonomes"

    Les pages d'erreur **n'héritent pas** de `base.html.twig` : elles sont autonomes avec leur propre CSS.
    Ainsi, elles fonctionnent même si l'erreur vient de vos templates ou de votre mise en page.

## Lever une erreur HTTP depuis un controller

La classe `ErrorHttpStatusException` permet d'interrompre le traitement et d'afficher une page d'erreur depuis un controller. Le second paramètre est le code HTTP.

Exemple : interdire l'accès à une page :

```php
<?php

namespace Controller;

use Studoo\EduFramework\Core\Controller\Request;
use Studoo\EduFramework\Core\Exception\ErrorHttpStatusException;

class AdminController
{
    public function index(Request $request): string|null
    {
        if ($request->get('token') !== 'mon-token-secret') {
            // Affiche la page d'erreur 403 (accès interdit)
            throw new ErrorHttpStatusException('Zone réservée aux administrateurs', 403);
        }

        // ... traitement normal
    }
}
```

Les codes supportés sont `403`, `404` et `405` ; tout autre code affiche la page 500.

## Le mode développement

En mode développement (`APP_ENV=dev` dans le fichier ".env"), les pages d'erreur affichent le **message de l'exception** dans un encadré rouge : utile pour comprendre et corriger l'erreur.

En production (ou si `APP_ENV` n'est pas défini), ce message est **masqué** : le visiteur ne voit qu'une page d'erreur générique. C'est une bonne pratique de sécurité : les messages d'erreur peuvent révéler des informations internes (chemins, requêtes, configuration).

!!! warning "Attention"

    Les erreurs de configuration (Exemple : le fichier "routes.yaml" introuvable) déclenchent aussi la page 500.
    Le message d'origine s'affiche en mode développement pour vous aider à corriger le problème.
