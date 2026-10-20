# Migrer de la version v2.3 à v2.8

## Introduction
Les versions 2.4 à 2.8 du framework apportent de nombreuses nouveautés : controllers à plusieurs routes, téléversement de fichiers, cache Twig, pages d'erreur mises en forme, protection CSRF, exécution dans Docker avec FrankenPHP, requêtes SQL et profiler de code dans la barre de debug. \
La plupart sont **rétro-compatibles**, mais quelques actions sont nécessaires sur votre projet. Je vous invite à lire attentivement cette page avant de migrer.
Lien sur les release notes : [v2.4](../changelog.md#v240-08102026), [v2.5](../changelog.md#v250-08102026), [v2.6](../changelog.md#v260-14102026), [v2.7](../changelog.md#v270-17102026), [v2.8](../changelog.md#v280-20102026)

!!! info "La version de PHP ne change pas"

    Comme la v2.3, la v2.8 nécessite **PHP 8.4** minimum. Si votre projet fonctionne en v2.3, votre version de PHP convient.

## Comment migrer ?
Pour migrer de la version 2.3 à la version 2.8, vous devez suivre les étapes suivantes :

1. **Analyser les changements** : Analysez les changements apportés par la nouvelle version pour comprendre leur impact sur votre projet.
2. **Sauvegarder vos données** : Assurez-vous de sauvegarder vos données avant de procéder à la migration pour éviter toute perte (voir l'étape 3.1 si vous utilisez la base de données Docker).
3. Faire les actions suivantes :

3.1 **Sauvegarder la base de données (services Docker)** :

!!! danger "MySQL est remplacé par MariaDB"

    En v2.3, le service `database` du fichier `compose.yaml` utilisait l'image **MySQL 8.0**. En v2.8, il utilise **MariaDB**.
    Les deux services stockent leurs données dans le même dossier `var/dbdata`, mais **MariaDB ne sait pas reprendre les fichiers d'une base MySQL 8**.
    Il faut donc **exporter vos données avant de changer le fichier `compose.yaml`**, puis les réimporter dans MariaDB (étape 3.6).

Si vous n'utilisez pas la base de données des services Docker (base SQLite, ou MySQL installé directement sur votre machine), passez à l'étape 3.2.

Les services v2.3 étant démarrés, exportez votre base dans le dossier `var/` (non versionné) :

```Bash
docker exec edu-web-database mysqldump -uroot -pstudoo --databases app_db > var/backup-app_db.sql
```

!!! note "Adapter la commande"

    `edu-web-database` est le nom du conteneur en v2.3, `studoo` le mot de passe root et `app_db` le nom de la base.
    Remplacez-les si vous les avez modifiés dans votre fichier `compose.yaml`.

Vérifiez que le fichier contient bien vos tables, puis arrêtez les services v2.3 :

```Bash
grep -c "CREATE TABLE" var/backup-app_db.sql
docker compose down
```

Enfin, mettez de côté l'ancien dossier de données MySQL (vous pourrez le supprimer une fois la migration validée) :

```Bash
mv var/dbdata var/dbdata-mysql8
```

3.2 **Mettre à jour le framework** :
```Bash
composer update studoo/edu-framework
```

Résultat :
```bash
[...]
Package operations: 0 installs, 1 update, 0 removals
  - Upgrading studoo/edu-framework (2.3.x-dev xxxxxxx => 2.8.x-dev xxxxxxx): Extracting archive
[...]
```

3.3 **Ajouter les templates des pages d'erreur** :

!!! warning "Action obligatoire"

    Depuis la v2.5, les pages d'erreur utilisent deux nouveaux templates : `http-500.html.twig` (erreur inattendue, remplace `http-Default.html.twig`) et `http-403.html.twig` (accès interdit).
    S'ils sont absents de votre projet, **toute erreur provoque une exception Twig** au lieu d'afficher la page d'erreur.

Téléchargez les templates dans le dossier `app/Template/error/` de votre projet (les templates `http-404` et `http-405` sont également mis en forme) :

=== ":fontawesome-brands-windows: WINDOWS"

    Ouvrir un terminal git bash à la racine de votre projet

    ```bash
    for code in 403 404 405 500; do
      curl --ssl-no-revoke -o app/Template/error/http-$code.html.twig https://raw.githubusercontent.com/studoo-app/edu-framework/2.x/app/Template/error/http-$code.html.twig
    done
    rm app/Template/error/http-Default.html.twig
    ```

=== ":fontawesome-brands-apple: MAC OS"

    Ouvrir un terminal à la racine de votre projet

    ```bash
    for code in 403 404 405 500; do
      curl -sS -o app/Template/error/http-$code.html.twig https://raw.githubusercontent.com/studoo-app/edu-framework/2.x/app/Template/error/http-$code.html.twig
    done
    rm app/Template/error/http-Default.html.twig
    ```

!!! tip "Vous avez personnalisé vos pages d'erreur ?"

    Ne remplacez pas vos templates `http-404` et `http-405` : téléchargez seulement `http-403` et `http-500`, puis reportez vos personnalisations de `http-Default.html.twig` dans `http-500.html.twig`.
    Les templates reçoivent la variable `code` (code HTTP) et, pour la page 500, `exception_message` (message de l'exception, affiché uniquement en mode développement).

3.4 **Déclarer le dossier du cache dans le fichier `public/index.php`** :

Depuis la v2.4, les templates Twig compilés sont mis en cache. Ajoutez la clé `cache_path` à la configuration :

```diff
  'base_path'         => __DIR__ . '/../',
  'twig_path'         => __DIR__ . '/../app/Template',
  'route_config_path' => __DIR__ . '/../app/Config/',
++'cache_path'        => __DIR__ . '/../var/cache/',
  'sqlite_path'       => __DIR__ . '/../var/sqlite/',
  'sqlite_logs_path'  => __DIR__ . '/../var/logs/'
```

Sans cette clé, le framework utilise le chemin relatif `./var/cache/`, qui dépend du dossier depuis lequel le serveur est lancé.

3.5 **Mettre à jour le fichier `.env`** :

Ajoutez les nouvelles variables à votre fichier `.env` (les valeurs ci-dessous sont les valeurs par défaut) :

```dotenv
## << Config profiler de code (barre de debug, mode dev uniquement)
EDU_PROFILER=trigger
EDU_PROFILER_KEEP=50
EDU_PROFILER_BUILD=1
## >> Config profiler de code

## << Config Docker (ports exposés sur l'hôte, voir compose.yaml)
HTTP_PORT=80
HTTPS_PORT=443
SERVER_NAME=:80
PMA_PORT=8081
DBGATE_PORT=8082
MAILER_SMTP_PORT=1025
MAILER_UI_PORT=8025
## >> Config Docker
```

!!! note "La variable APP_NAME"

    Les noms des conteneurs Docker sont maintenant préfixés par la variable `APP_NAME` du `.env` (Exemple : `edu-framework-database` au lieu de `edu-web-database`).
    Donnez un nom différent à chaque projet pour pouvoir en faire tourner plusieurs sur la même machine.

Le détail de chaque variable est donné dans la page [L'application dans Docker](../installation/docker.md#les-variables-denvironnement).

3.6 **Mettre à jour les fichiers Docker et réimporter la base** :

La v2.8 ajoute un service web **FrankenPHP** qui exécute l'application dans Docker. Il nécessite trois fichiers : `compose.yaml` (mis à jour), `Dockerfile` et `Caddyfile` (nouveaux).

=== ":fontawesome-brands-windows: WINDOWS"

    Ouvrir un terminal git bash à la racine de votre projet

    ```bash
    for file in compose.yaml Dockerfile Caddyfile; do
      curl --ssl-no-revoke -o $file https://raw.githubusercontent.com/studoo-app/edu-framework/2.x/$file
    done
    ```

=== ":fontawesome-brands-apple: MAC OS"

    Ouvrir un terminal à la racine de votre projet

    ```bash
    for file in compose.yaml Dockerfile Caddyfile; do
      curl -sS -o $file https://raw.githubusercontent.com/studoo-app/edu-framework/2.x/$file
    done
    ```

Démarrez la base MariaDB, puis réimportez la sauvegarde de l'étape 3.1 (remplacez `edu-framework` par la valeur de `APP_NAME` dans votre `.env`) :

```Bash
docker compose up -d database
docker exec -i edu-framework-database mariadb -uroot -pstudoo < var/backup-app_db.sql
```

!!! bug "Unknown collation: 'utf8mb4_0900_ai_ci'"

    Cette collation est propre à MySQL 8. Si l'import échoue avec cette erreur, remplacez-la dans la sauvegarde puis relancez l'import :

    ```Bash
    sed -i.bak 's/utf8mb4_0900_ai_ci/utf8mb4_unicode_ci/g' var/backup-app_db.sql
    ```

Démarrez enfin l'ensemble des services, application comprise (la première construction de l'image prend quelques minutes) :

```Bash
docker compose up -d
```

L'application est disponible sur `http://localhost`. Pour plus de détails, consulter la page [L'application dans Docker](../installation/docker.md).

3.7 **Vider le cache et vérifier la version** :
```Bash
php bin/edu cache:clear
php bin/edu --version
```

Résultat :
```bash
EduFramework v2.8.x-xxxx
```

## Les changements

### **La base de données Docker passe à MariaDB**
Le service `database` utilise l'image `mariadb:latest` au lieu de `mysql:8.0` (voir l'étape 3.1). La variable `DB_TYPE=mysql` de votre `.env` reste valable : MariaDB est compatible avec le pilote PDO MySQL.

### **Les pages d'erreur**
Le template `http-Default.html.twig` est renommé `http-500.html.twig`, et le nouveau `HttpError403Controller` utilise `http-403.html.twig` (voir l'étape 3.3).
Toute exception non gérée affiche maintenant la page 500, avec le message de l'exception **en mode développement uniquement** (masqué en production).

### **La journalisation des requêtes**
Les requêtes sont maintenant journalisées par le framework lui-même, quel que soit le serveur (serveur PHP intégré ou FrankenPHP), et non plus en analysant la sortie de `php bin/edu start`. Conséquences :

- les **fichiers statiques** (CSS, images, ...) servis directement par le serveur web n'apparaissent plus dans le profiler ;
- la barre de pagination du profiler est remplacée par le sélecteur « Résultats » et les raccourcis « 10 derniers » / « Dernier ».

La base des logs (`var/logs/core_logs.sqlite`) est **mise à niveau automatiquement** : aucune action n'est nécessaire.

### **La connexion à la base de données en mode dev**
En mode développement, `DatabaseService::getConnect()` retourne une connexion `TraceablePDO`, qui **étend `PDO`** et mesure chaque requête SQL. Votre code applicatif n'a pas à changer, sauf s'il compare la classe exacte de la connexion (`get_class($pdo) === PDO::class`) : utilisez plutôt `$pdo instanceof PDO`. En production, la connexion reste un `PDO` natif.

### **Les dépendances**
La dépendance `zircote/swagger-php` passe en version **5.8** : la commande `vendor/bin/openapi` fonctionne désormais sans erreur sous PHP 8.4 et 8.5. Les attributs `OpenApi\Attributes` restent identiques, aucune action n'est nécessaire.

## Les nouveautés

### **Un controller, plusieurs routes** (v2.4)
La clé `controller` du fichier `app/Config/routes.yaml` accepte la syntaxe `Controller\VilleController::index` pour appeler une méthode précise. Sans méthode, `execute()` est appelée comme avant.

Pour plus de détails, consulter la section [Un controller, plusieurs routes](../build/controller-edu.md#un-controller-plusieurs-routes-plusieurs-methodes).

### **Le téléversement de fichiers** (v2.4)
L'objet `Request` gère les fichiers envoyés par un formulaire : `hasFile()`, `getFile()`, `getFiles()`, puis `isValid()`, `getExtension()` et `move()` sur chaque fichier.

Pour plus de détails, consulter la page [La requête HTTP](../boost/resquet.md).

### **Le cache Twig et la commande cache:clear** (v2.4)
Les templates compilés sont stockés dans `var/cache/twig` et recompilés automatiquement lorsqu'ils sont modifiés. La commande `php bin/edu cache:clear` supprime le cache de l'application.

### **La commande make:entity** (v2.5)
La commande `php bin/edu make:entity` génère une entité (`app/Entity`) et son repository (`app/Repository`), en mode interactif ou avec l'option `--fields`.

Pour plus de détails, consulter la page [Les commandes Edu](../installation/command-edu.md).

### **La protection CSRF** (v2.6)
Le nouveau service `CsrfService` protège vos formulaires contre les attaques CSRF : fonction Twig `csrf_token('nom_route')` dans le formulaire, et `Request::isCsrfTokenValid('nom_route')` dans le controller.

Pour plus de détails, consulter la page [La protection CSRF](../build/csrf.md).

### **L'application dans Docker avec FrankenPHP** (v2.7)
Le service `frankenphp` exécute l'application dans Docker, en HTTP ou en HTTPS local. Le démarrage classique avec `php bin/edu start` reste disponible.

Pour plus de détails, consulter la page [L'application dans Docker](../installation/docker.md).

### **La barre de debug et le profiler** (v2.7 et v2.8)
La barre de debug et le profiler s'enrichissent :

- **descriptif d'une requête** et **message d'erreur** journalisé avec la requête (v2.7) ;
- **requêtes SQL** de chaque requête HTTP : bloc SQL de la barre et vue SQL du profiler (v2.7) ;
- **services de développement** (Mailpit, DbGate, phpMyAdmin, ...) listés avec leurs URLs dans le bloc DEV (v2.7) ;
- **profiler de code** : temps passé dans chaque fonction PHP, export en flamegraph (v2.8).

Pour plus de détails, consulter la page [La barre de debug et le profiler](../boost/debug-bar.md).
