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
