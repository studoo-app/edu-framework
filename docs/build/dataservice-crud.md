# Gestion des données

## Introduction

Nous avons vu dans la documentation précédente comment gérer une page avec un controller et un formulaire en méthode POST.
La suite logique est de comprendre comment gérer les données dans une base de données.

!!! Prérequis "PHP Data Objects (PDO)"

    PDO est un moyen de communiquer de manière efficace avec une base de données en PHP, tout en gardant la flexibilité de changer de type de base de données si nécessaire, sans avoir à réécrire votre code.

    Pour plus d'informations sur l'objet PDO, vous pouvez consulter la [documentation officielle](https://www.php.net/manual/fr/book.pdo.php){:target="_blank"}.


## Prérequis

Vous devez activer le service DatabaseService pour utiliser une base de données.
L'activation du service DatabaseService s'oppère dans le fichier .env

```diff
-- DB_HOST_STATUS=false
++ DB_HOST_STATUS=true
```

!!! info "Pour aller plus loin"

    Vous devez avoir lu la documentation sur la [DatabaseService : La gestion des données](../boost/dataservice.md)


## Schéma de la base de données

Pour cet exemple, nous allons utiliser une base de données avec une table `ville` qui contient les champs suivants :

```sql
CREATE TABLE ville (
   id INT AUTO_INCREMENT PRIMARY KEY,
   nom VARCHAR(255) NOT NULL,
   code_postal VARCHAR(10) NULL,
   nombre_habitant INT NULL
);

INSERT INTO ville (nom, code_postal, nombre_habitant) VALUES ('Paris', '75000', 2200000);
INSERT INTO ville (nom, code_postal, nombre_habitant) VALUES ('Marseille', '13000', 800000);
INSERT INTO ville (nom, code_postal, nombre_habitant) VALUES ('Lyon', '69000', 500000);
```

Dans la configuration Docker, nous proposons d'utiliser PhpMyAdmin pour gérer la base de données.


### Read

Pour lire les enregistrements de la base de données, vous devez utiliser la méthode `select` de la classe `DatabaseService`.

```php
// Recupere l'object PHP PDO
$comBase = DatabaseService::getConnect();
// Requete SQL
$statementPDO = $comBase->query("SELECT * FROM ville");
// Recuperation des résultats de la requete
$users = $statementPDO->fetchAll();
```

## Utilisation avancée

Pour une utilisation plus avancée et sécurisée du CRUD, vous pouvez utiliser les méthodes `prepare`, `execute`, `bindParam` de la classe `DatabaseService`.

```php
// Recupere l'object PHP PDO
$comBase = DatabaseService::getConnect();
// Requete SQL préparée
$statementPDO = $comBase->prepare("SELECT * FROM ville WHERE id = :id");
$statementPDO->execute(['id' => 1]);
// Recuperation des résultats de la requete
$users = $statementPDO->fetchAll();
```

!!! warning "Sujet cybersécurité"

    Pourquoi utiliser une requête préparée ?

    - Pour éviter les attaques par injection SQL
    - Pour améliorer les performances des requêtes SQL
    - Pour faciliter la gestion des paramètres de la requête
    - Pour rendre le code plus lisible et maintenable
