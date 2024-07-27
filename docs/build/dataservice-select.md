# Gestion des données

## Introduction

Nous avons vu dans la documentation précédente comment gérer une page avec un controller et un formulaire en méthode POST.
La suite logique est de comprendre comment gérer les données dans une base de données.

!!! Prérequis "PHP Data Objects (PDO)"

    PDO est un moyen de communiquer de manière efficace avec une base de données en PHP, tout en gardant la flexibilité de changer de type de base de données si nécessaire, sans avoir à réécrire votre code.

    Pour plus d'informations sur l'objet PDO, vous pouvez consulter la [documentation officielle](https://www.php.net/manual/fr/book.pdo.php){:target="_blank"}.


## Prérequis

Vous devez activer le service DatabaseService pour utiliser une base de données MySQL, MariaDB ou SQLite.

!!! info "Lecture obligatoire"

    Vous devez avoir lu la documentation sur la [DatabaseService : La gestion des données](../boost/dataservice.md)
    
    L'activation du service DatabaseService s'oppère dans le fichier .env de votre projet.
    ```diff
    -- DB_HOST_STATUS=false
    ++ DB_HOST_STATUS=true
    ```

## Schéma de la base de données

Pour cet exemple, nous allons utiliser une base de données avec une table `ville` qui contient les champs suivants :


=== "MYSQL/MARIADB"

    ```sql
    CREATE TABLE ville (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(255) NOT NULL,
        code_postal VARCHAR(10) NULL,
        nombre_habitant INT NULL
    );
    ```

=== "SQLITE"

    ```sql
    CREATE TABLE ville (
        id integer not null constraint ville_pk primary key autoincrement,
        code_postal     TEXT,
        nombre_habitant integer,
        nom             TEXT
    );
    ```



Jeu de donnée pour les bases Mysql/Sqlite

```sql
INSERT INTO ville (nom, code_postal, nombre_habitant) VALUES ('Paris', '75000', 2200000);
INSERT INTO ville (nom, code_postal, nombre_habitant) VALUES ('Marseille', '13000', 800000);
INSERT INTO ville (nom, code_postal, nombre_habitant) VALUES ('Lyon', '69000', 500000);
```

## Création de l'entité Ville et de la couche modèle (Repository)

Nous allons reprendre la page sur [Construire un formulaire](build/controller-edu-post.md)

Voici l'arborecence actuelle de notre projet :

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

Nous allons ajouter deux dossiers dans le dossier `app` pour gérer les données :

- `Entity` : pour les entités (classes qui représentent les données de la base de données)
- `Repository` : pour les classes qui permettent de récupérer les données de la base de données 

``` hl_lines="6 7 8 9"
├── app
│   ├── Config
│   │   └── routes.yaml
│   ├── Controller
│   │   └── VilleController.php
│   ├── Entity
│   │   └── Ville.php
│   ├── Repository
│   │   └── VilleRepository.php
│   └── Template
│       ├── base.html.twig
│       └── ville
│           └── ville.html.twig
```

Selon le schéma de la base de données, nous allons créer une entité `Ville` et un repository `VilleRepository`.

### Création de l'entité Ville

Nous allons créer une entité `Ville` dans le dossier `app/Entity`.

```php
<?php

namespace Entity;

class Ville
{
    private int $id;
    private string $nom;
    private string $code_postal;
    private int $nombre_habitant;

    public function __construct($id, $nom, $code_postal, $nombre_habitant)
    {
        $this->id = $id;
        $this->nom = $nom;
        $this->code_postal = $code_postal;
        $this->nombre_habitant = $nombre_habitant;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getNom()
    {
        return $this->nom;
    }

    public function getCodePostal()
    {
        return $this->code_postal;
    }

    public function getNombreHabitant()
    {
        return $this->nombre_habitant;
    }
}
```

### Création du repository VilleRepository

Nous allons créer un repository `VilleRepository` dans le dossier `app/Repository`.

```php
<?php

namespace Repository;

use Entity\Ville;
use Studoo\EduFramework\Core\Service\DatabaseService;

class VilleRepository
{
    public function getVilles(): array
    {
        $villes = [];

        $DataService = DatabaseService::getConnect();
        $stmt = $DataService->query('SELECT * FROM ville');

        while ($row = $stmt->fetch()) {
            $villes[] = new Ville($row['id'], $row['nom'], $row['code_postal'], $row['nombre_habitant']);
        }

        return $villes;
    }
}
```

### Modification du controller VilleController

Nous allons modifier le controller `VilleController` pour récupérer les données de la base de données.

```diff
<?php

namespace Controller;

+ use Repository\VilleRepository;
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
 		        "request" => $request,
 		        "add_ville" => $request->get('nom_ville'),
+ 		        "villes" => (new VilleRepository())->getVilles()
		    ]
		);
	}
}
```

### Modification de la vue ville.html.twig

Nous allons modifier la vue `ville.html.twig` pour afficher les données de la base de données.

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
        <form action="{{ getNameToPath('ville') }}" method="post">
            <label for="nom_ville">Ville</label>
            <input type="text" id="nom_ville" name="nom_ville">
            <input type="submit" value="Envoyer">
        </form>

+        <h2>Liste des villes</h2>
+        <ul>
+            {% for ville in villes %}
+                <li>{{ ville.getNom() }} ({{ ville.getCodePostal() }}) - {{ ville.getNombreHabitant() }} habitants</li>
+            {% endfor %}
+        </ul>
{% endblock %}
```