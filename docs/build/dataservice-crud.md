# Faire un CRUD

## Introduction

Le service DatabaseService permet de gérer les requêtes SQL sur la base de données.
Il est basé sur l'objet PDO de PHP.

!!! info "PDO"

    Pour plus d'informations sur l'objet PDO, vous pouvez consulter la [documentation officielle](https://www.php.net/manual/fr/book.pdo.php){:target="_blank"}.

!!! warning "Prérequis"

    Vous devez avoir lu la documentation sur la [DatabaseService : La gestion des données](../boost/dataservice.md) avant de continuer sur le CRUD

    L'activation du service DatabaseService est obligatoire pour utiliser le CRUD, il s'oppère dans le fichier .env

    ```diff
    -- DB_HOST_STATUS=false
    ++ DB_HOST_STATUS=true
    ```

## CRUD

Le CRUD (Create, Read, Update, Delete) est un ensemble d'opérations de base pour la gestion des données dans une base de données.

### Create

Pour créer un nouvel enregistrement dans la base de données, vous devez utiliser la méthode `insert` de la classe `DatabaseService`.

```php
// Recupere l'object PHP PDO
$comBase = DatabaseService::getConnect();
// Requete SQL
$statementPDO = $comBase->insert("INSERT INTO user (name, email) VALUES ('John Doe', 'ben@toto.fr')");
```

### Read

Pour lire les enregistrements de la base de données, vous devez utiliser la méthode `select` de la classe `DatabaseService`.

```php
// Recupere l'object PHP PDO
$comBase = DatabaseService::getConnect();
// Requete SQL
$statementPDO = $comBase->query("SELECT * FROM user");
// Recuperation des résultats de la requete
$users = $statementPDO->fetchAll();
```

### Update

Pour mettre à jour un enregistrement dans la base de données, vous devez utiliser la méthode `update` de la classe `DatabaseService`.

```php
// Recupere l'object PHP PDO
$comBase = DatabaseService::getConnect();
// Requete SQL
$statementPDO = $comBase->query("UPDATE user SET name='Jane Doe' WHERE id=1");
```

### Delete

Pour supprimer un enregistrement de la base de données, vous devez utiliser la méthode `delete` de la classe `DatabaseService`.

```php
// Recupere l'object PHP PDO
$comBase = DatabaseService::getConnect();
// Requete SQL
$statementPDO = $comBase->query("DELETE FROM user WHERE id=1");
```

## Utilisation avancée

Pour une utilisation plus avancée et sécurisée du CRUD, vous pouvez utiliser les méthodes `prepare`, `execute`, `bindParam` de la classe `DatabaseService`.

```php
// Recupere l'object PHP PDO
$comBase = DatabaseService::getConnect();
// Requete SQL préparée
$statementPDO = $comBase->prepare("SELECT * FROM user WHERE id = :id");
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

## Exercice pratique

Pour vous entrainer, voici un use case sur la gestion des villes avec une base de données :

- [STEP 1 : Use case "VILLE"](use-case-ville.md)