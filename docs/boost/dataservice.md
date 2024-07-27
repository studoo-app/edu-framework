# DatabaseService : La gestion des données

DatabaseService est une classe qui permet de gérer les données de la base de données. Elle permet de faire des requêtes SQL, de récupérer des données, d'insérer, de mettre à jour et de supprimer des données.

Cette classe est de type Singleton, ce qui signifie qu'il n'y a qu'une seule instance de cette classe dans l'application.
Elle est instanciée automatiquement dans le framework et est accessible dans les controllers.

DatabaseService implémente l'interface de [PHP Data Objects (PDO)](https://www.php.net/manual/fr/book.pdo.php){:target="_blank"} pour autoriser l'accès de PHP aux bases de données

!!! info "Pour aller plus loin"

    Pour plus d'informations sur l'objet PDO, vous pouvez consulter la [documentation officielle](https://www.php.net/manual/fr/book.pdo.php){:target="_blank"}.


## Prérequis
Vous devez avoir une base de données MySQL, MariaDB ou SQLite pour utiliser cette classe.
Quelques informations de connexion à la base de données sont nécessaires pour configurer la connexion à la base de données.


## Installation

Pour utiliser la classe DatabaseService, vous devez renseigner les informations de connexion à la base de données dans le fichier `.env` de votre projet.

Vous devez modifier les variables suivantes dans le fichier `.env` :


| Variable | Valeur par défaut | Description                                                                       |
|--------|-------------------|-----------------------------------------------------------------------------------|
| **DB_HOST_STATUS**       | false             | `true` pour activer la connexion à la base de données, `false` pour la désactiver |
| **DB_TYPE**  | mysql             | le type de base de données `mysql` `mariadb` `sqlite`                             |
| **DB_HOST**  | 127.0.0.1         | l'adresse IP ou le nom du serveur de base de données                              |
| **DB_SOCKET**  | 3306              | le port (socket) de la base de données                                            |
| **DB_USER**  | root              | le nom de l'utilisateur de la base de données                                      |
| **DB_PASSWORD**  | root              | le mot de passe de l'utilisateur de la base de données                                   |
| **DB_NAME**  | app_db              | le nom de la base de données                                  |

Exemple de configuration de la base de données dans le fichier `.env` :

```dotenv
## << Config Database
## pour activer la connexion à la base de données, il faut mettre DB_HOST_STATUS=true
DB_HOST_STATUS=false
## Type de base de données (mysql,mariadb)
DB_TYPE=mysql
## IP ou nom du serveur de base de données
DB_HOST=127.0.0.1
## Port de la base de données
DB_SOCKET=3306
## Nom de l'utilisateur de la base de données
DB_USER=root
## Mot de passe de l'utilisateur de la base de données
DB_PASSWORD=root
## Nom de la base de données
DB_NAME=app_db
## >> Config Database
```

## Utilisation simple

Pour utiliser la classe DatabaseService, voici un exemple simple :

```php
// Recupere l'object PHP PDO
$comBase = DatabaseService::getConnect();
// Requete SQL
$statementPDO = $comBase->query("SELECT * FROM user");
// Recuperation des résultats de la requete
$users = $statementPDO->fetchAll();
```

$commBase est un objet de type PDO qui permet de faire des requêtes SQL sur la base de données.
$commBase->query() permet de faire une requête SQL sur la base de données.
$statementPDO est un objet de type PDOStatement qui contient les résultats de la requête.
$statementPDO->fetchAll() permet de récupérer les résultats de la requête.

## Utilisation avancée

Pour une utilisation plus avancée de la classe DatabaseService, vous pouvez utiliser les méthodes suivantes :

### query($sql)

La méthode query($sql) permet de faire une requête SQL sur la base de données.

```php
$statementPDO = $comBase->query("SELECT * FROM user");
```

### prepare($sql)

La méthode prepare($sql) permet de préparer une requête SQL sur la base de données.

```php
$statementPDO = $comBase->prepare("SELECT * FROM user WHERE id = :id");
$statementPDO->execute(['id' => 1]);
```

### execute($params)

La méthode execute($params) permet d'exécuter une requête préparée avec des paramètres.

```php
$statementPDO->execute(['id' => 1]);
```

### fetchAll()

La méthode fetchAll() permet de récupérer tous les résultats de la requête.

```php
$users = $statementPDO->fetchAll();
```

### fetch()

La méthode fetch() permet de récupérer un seul résultat de la requête.

```php
$user = $statementPDO->fetch();
```

