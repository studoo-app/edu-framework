# Route : générer une URL à partir du nom d'une route

La classe `Route` permet de reconstruire l'URL d'une route à partir de son **nom**, plutôt que d'écrire son chemin en dur dans les templates ou les controllers.

Cette fonctionnalité est disponible à partir de la version **v2.1.0**.

!!! info "Le nom d'une route"

    Le nom d'une route est la **clé principale** définie dans le fichier de configuration des routes `app/Config/routes.yaml` :

    ```yaml
    villeread:                    # <-- nom de la route
      uri: /ville
      controller: Controller\VilleReadController
      httpMethod: [GET]
    villeupdate:
      uri: '/ville/{id}/update'
      controller: Controller\VilleUpdateController
      httpMethod: [GET, POST]
    ```

    Si vous changez l'`uri` d'une route, tous les liens générés par `getNameToPath()` suivent automatiquement.

## Utilisation dans un template Twig

`getNameToPath` est une fonction Twig disponible dans tous vos templates :

```twig
{{ getNameToPath('villeread') }}
```

Résultat :

```text
/ville
```

### Avec des paramètres de route

Si la route contient des paramètres (ex. `/ville/{id}/update`), vous devez les passer en second argument sous la forme d'un tableau associatif :

```twig
{{ getNameToPath('villeupdate', {'id': ville.id}) }}
```

Résultat (pour `ville.id` = 42) :

```text
/ville/42/update
```

Exemple dans un lien :

```twig
<a href="{{ getNameToPath('villeupdate', {'id': ville.id}) }}">Modifier</a>
```

## Utilisation dans un controller

La même fonctionnalité est disponible en PHP via la classe `Route` :

```php
use Studoo\EduFramework\Core\Controller\Route;

$url = (new Route())->getNameToPath('villeupdate', ['id' => 42]);
// $url contient : /ville/42/update
```

C'est utile pour les redirections après un traitement de formulaire :

```php
header('Location: ' . (new Route())->getNameToPath('villeread'));
```

## Gestion des erreurs

Si le nom de la route ou un paramètre est incorrect, une exception `BadRouteException` est levée :

| Cause                                  | Message                    |
|----------------------------------------|----------------------------|
| Le nom de la route n'existe pas         | `La route n'existe pas`    |
| Un paramètre de la route est manquant   | `Le paramètre n'existe pas` |

```php
use Studoo\EduFramework\Core\Exception\BadRouteException;

try {
    $url = (new Route())->getNameToPath('villeupdate'); // paramètre id manquant
} catch (BadRouteException $e) {
    // Le paramètre n'existe pas
}
```

!!! warning "Routes avec paramètre obligatoire"

    Une route avec un paramètre obligatoire (ex. `/ville/{id}/delete`) **doit** recevoir la valeur de ce paramètre.
    En revanche, un paramètre optionnel (ex. `/user/{id}[/{name}]`) peut être omis :
    `getNameToPath('user', {'id': 1})` génère `/user/1`.
