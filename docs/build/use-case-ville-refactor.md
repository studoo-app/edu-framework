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

Vous devez maintenant changer les noms dans les fichiers de controller et de vue.

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


