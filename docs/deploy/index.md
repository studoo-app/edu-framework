# Déployer son application

Votre application fonctionne sur votre poste : il est temps de la mettre en ligne, accessible à tous en HTTPS avec votre nom de domaine.

Edu-Framework s'appuie pour cela sur [FrankenDeploy](https://yoanbernabeu.github.io/frankendeploy/){:target="_blank"}, un outil en ligne de commande qui déploie une application [FrankenPHP](https://frankenphp.dev){:target="_blank"} sur un serveur (VPS) **Debian** ou **Ubuntu** : il installe Docker sur le serveur, obtient le certificat HTTPS (Let's Encrypt), crée la base de données et met à jour l'application sans coupure de service.

!!! warning "Outil expérimental"

    FrankenDeploy est jeune et conçu à l'origine pour Symfony : Edu-Framework fournit le `Dockerfile` et la configuration qui le rendent compatible. Consultez la [documentation de FrankenDeploy](https://yoanbernabeu.github.io/frankendeploy/){:target="_blank"} pour le détail de chaque commande.

## Comment ça marche ?

```mermaid
flowchart LR
    P["Votre poste<br/>frankendeploy deploy"] -->|"SSH"| S
    subgraph S["Serveur Debian (VPS)"]
        C["Caddy<br/>HTTPS :443"] -->|":8080"| A["Conteneur de l'application<br/>FrankenPHP + PHP 8.4"]
        A -->|"DATABASE_URL"| DB[("MariaDB")]
        A -.-> SH["Dossiers partagés<br/>.env.local, var/logs, var/sessions,<br/>var/sqlite, public/upload"]
    end
    N["Navigateur"] -->|"https://mon-app.fr"| C
```

1. Sur votre poste, FrankenDeploy construit l'**image Docker de production** à partir du `Dockerfile` du projet (stage `frankenphp_prod`)
2. L'image est envoyée sur le serveur et démarrée **à côté** de l'ancienne version
3. Quand la nouvelle version répond correctement (*healthcheck*), le trafic bascule dessus et l'ancienne est arrêtée
4. En cas d'échec, l'ancienne version continue de servir : vos utilisateurs ne voient rien

## Avant de démarrer

- [ ] Un **serveur** (VPS) sous Debian 12+ ou Ubuntu 22.04+, avec 2 Go de RAM
- [ ] Un accès **SSH par clé** à ce serveur, avec l'utilisateur `root` ou un utilisateur `sudo` sans mot de passe
- [ ] Un **nom de domaine** dont l'enregistrement DNS `A` pointe vers l'adresse IP du serveur
- [ ] **Docker Desktop** installé sur votre poste
- [ ] Le **CLI FrankenDeploy** installé sur votre poste :

=== "macOS"

    ```bash
    brew install yoanbernabeu/tap/frankendeploy
    ```

=== "Linux"

    ```bash
    curl -fsSL https://raw.githubusercontent.com/yoanbernabeu/frankendeploy/main/scripts/install.sh | sh
    ```

Vérifiez l'installation :

```bash
frankendeploy --version
```

!!! warning "Projet créé avant la version 2.9.0"

    L'image de production repose sur trois fichiers à la racine du projet : le `Dockerfile` (avec le stage `frankenphp_prod`), `docker-entrypoint.sh` et `.dockerignore`. Si votre projet a été créé avant la version 2.9.0, récupérez-les depuis le framework installé par Composer :

    ```bash
    cp vendor/studoo/edu-framework/Dockerfile vendor/studoo/edu-framework/docker-entrypoint.sh vendor/studoo/edu-framework/.dockerignore .
    ```

    Vérifiez aussi que le fichier `composer.lock` est versionné (il ne doit pas figurer dans le `.gitignore`) : l'image de production installe exactement les versions qu'il contient.

## Étape 1 : décrire le déploiement

La commande `make:deploy` génère le fichier `frankendeploy.yaml` à la racine du projet :

```bash
php bin/edu make:deploy --domain=mon-app.fr
```

| Option         | Description                                                    | Défaut              |
|----------------|----------------------------------------------------------------|---------------------|
| `--domain`     | nom de domaine de l'application                                | aucun               |
| `--name`       | nom de l'application sur le serveur (minuscules, tirets), unique sur le serveur | nom du dossier (obligatoire dans le conteneur Docker) |
| `--database`   | base de données : `mariadb`, `mysql`, `sqlite` ou `none`       | `mariadb`           |
| `--force`      | écrase un fichier `frankendeploy.yaml` existant                |                     |

!!! info "Dans le conteneur Docker de développement"

    Dans le [flux 100 % Docker](../installation/docker.md), le projet est monté dans le dossier `/app` du conteneur : précisez le nom de l'application, sinon tous les projets s'appelleraient `app` sur le serveur.

    ```bash
    docker compose exec frankenphp php bin/edu make:deploy --name=mon-app --domain=mon-app.fr
    ```

Le fichier généré ressemble à ceci :

```yaml
name: mon-app

php:
  version: "8.4"
  extensions: [pdo_sqlite, pdo_mysql, mbstring, zip, intl, opcache]

# Base de données gérée par FrankenDeploy (conteneur sur le serveur)
database:
  driver: mariadb
  version: "11"
  managed: true

deploy:
  domain: mon-app.fr
  healthcheck_path: /
  keep_releases: 5
  shared_files:
    - .env.local
  shared_dirs:
    - var/logs
    - var/sessions
    - var/sqlite
    - public/upload
```

!!! info "Le healthcheck"

    Avant de basculer le trafic, FrankenDeploy vérifie que la page `healthcheck_path` (par défaut `/`) répond avec un code HTTP 200. Docker surveille aussi l'application avec la ligne `HEALTHCHECK` du `Dockerfile`, utilisée notamment par `frankendeploy env set ... --reload`.

    Si votre page d'accueil nécessite d'être connecté, choisissez une autre route publique et modifiez **les deux** : `healthcheck_path` dans `frankendeploy.yaml` et l'URL de la ligne `HEALTHCHECK` du `Dockerfile` (`http://localhost:8080/...`).

Versionnez ce fichier avec votre projet :

```bash
git add frankendeploy.yaml
git commit -m "Configuration du déploiement"
```

## Étape 2 : préparer le serveur

Ces commandes ne sont à lancer **qu'une seule fois** par serveur. Enregistrez d'abord le serveur sous le nom `prod` :

```bash
frankendeploy server add prod deploy@mon-serveur.fr
```

Puis préparez-le (l'adresse e-mail sert au certificat Let's Encrypt) :

```bash
frankendeploy server setup prod --email moi@exemple.fr
```

Cette commande installe Docker, configure le pare-feu (SSH, HTTP, HTTPS), protège le serveur contre les attaques SSH (Fail2ban) et démarre le proxy Caddy qui gère le HTTPS.

## Étape 3 : configurer les variables d'environnement

En production, le fichier `.env` de votre poste **n'est pas envoyé** sur le serveur (il est exclu de l'image Docker). La configuration est stockée sur le serveur, dans un fichier `.env.local` propre à l'application :

```bash
frankendeploy env set prod APP_ENV=prod
```

FrankenDeploy exige aussi la variable `APP_SECRET` (héritée de Symfony) : lors du premier déploiement, il propose de la générer pour vous.

Pour un mot de passe ou une clé d'API, utilisez la saisie masquée :

```bash
frankendeploy env set prod MAILER_DSN --from-stdin
```

`env set` n'accepte **qu'une variable par commande**. Pour en définir plusieurs, répétez la commande ou envoyez un fichier :

```bash
frankendeploy env push prod .env.prod
```

Consultez les variables définies :

```bash
frankendeploy env list prod
```

!!! danger "Le caractère # dans une valeur"

    FrankenDeploy écrit les valeurs sans guillemets dans le `.env.local` : à la lecture, tout ce qui suit un `#` est ignoré (`S3cr#t` devient `S3cr`). Évitez le `#` dans les mots de passe. Dans une URL (`DATABASE_URL`, `MAILER_DSN`), encodez les caractères spéciaux de l'utilisateur et du mot de passe : `#` => `%23`, `@` => `%40`, `/` => `%2F`, `?` => `%3F`.

    Une `DATABASE_URL` mal formée arrête l'application avec le message « La variable DATABASE_URL est invalide ».

!!! info "La base de données est configurée automatiquement"

    Avec une base gérée (`managed: true`), FrankenDeploy crée le conteneur MariaDB et transmet à l'application la variable `DATABASE_URL` :

    ```
    mysql://mon-app:motdepasse@mon-app-db:3306/mon_app
    ```

    Edu-Framework en déduit automatiquement les variables `DB_HOST_STATUS`, `DB_TYPE`, `DB_HOST`, `DB_SOCKET`, `DB_USER`, `DB_PASSWORD` et `DB_NAME`. Une variable `DB_*` définie dans le `.env.local` reste prioritaire.

    Cette `DATABASE_URL` n'est pas stockée dans le `.env.local` : `frankendeploy env list prod` ne l'affiche pas. Pour la consulter :

    ```bash
    frankendeploy exec prod printenv DATABASE_URL
    ```

!!! info "Avec une base SQLite (`--database=sqlite`)"

    Aucune `DATABASE_URL` n'est transmise : définissez les variables une par une.

    ```bash
    frankendeploy env set prod DB_HOST_STATUS=true
    ```

    ```bash
    frankendeploy env set prod DB_TYPE=sqlite
    ```

    ```bash
    frankendeploy env set prod DB_NAME=app_db
    ```

    Le fichier `var/sqlite/app_db.sqlite` est conservé entre les déploiements (dossier partagé). Au premier déploiement, il est vide.

### Ordre de lecture des variables

| Priorité | Source                                            | Usage                                |
|----------|---------------------------------------------------|--------------------------------------|
| 1        | variables d'environnement (Docker, serveur web)   | `APP_ENV`, `DATABASE_URL` en production |
| 2        | fichier `.env.local`                              | secrets de production, jamais versionné |
| 3        | fichier `.env`                                    | configuration de développement       |

## Étape 4 : vérifier puis déployer

Vérifiez que tout est prêt (connexion SSH, DNS, Docker) :

```bash
frankendeploy doctor prod
```

Lancez le déploiement :

```bash
frankendeploy deploy prod
```

!!! tip "Mac avec puce Apple (M1, M2...)"

    Votre Mac (ARM) et le serveur (souvent x86_64) n'ont pas la même architecture. Si la construction de l'image est trop lente, construisez-la directement sur le serveur :

    ```bash
    frankendeploy deploy prod --remote-build
    ```

Votre application est en ligne sur `https://mon-app.fr` :tada:

Pour chaque nouvelle version, il suffit de relancer `frankendeploy deploy prod`.

## Importer sa base de données

Au premier déploiement, la base MariaDB est vide. Pour importer le contenu de votre base de développement, exportez-la depuis votre poste (phpMyAdmin ou dbgate) dans un fichier `app_db.sql`, puis envoyez-le sur le serveur :

```bash
scp app_db.sql deploy@mon-serveur.fr:
```

Connectez-vous en SSH au serveur et importez le fichier dans le conteneur `mon-app-db`. Les identifiants sont lus dans les variables du conteneur : aucun mot de passe à saisir.

=== "MariaDB"

    ```bash
    docker exec -i mon-app-db sh -c 'exec mariadb -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"' < app_db.sql
    ```

=== "MySQL"

    ```bash
    docker exec -i mon-app-db sh -c 'exec mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' < app_db.sql
    ```

!!! info "Les identifiants de la base"

    FrankenDeploy les enregistre sur le serveur dans le fichier `/opt/frankendeploy/apps/mon-app/shared/.db_credentials` (lisible avec `sudo cat`).

!!! warning "Sauvegardes"

    FrankenDeploy ne sauvegarde automatiquement la base qu'avant les migrations Doctrine, qu'Edu-Framework n'utilise pas. Pensez à planifier vous-même des sauvegardes (`mariadb-dump`) sur le serveur.

## Les fichiers conservés entre deux déploiements

Le code de l'application est **dans l'image Docker** : à chaque déploiement, le conteneur est remplacé. Seuls les fichiers et dossiers partagés (`shared_files` et `shared_dirs`) sont conservés :

| Chemin            | Contenu                                                     |
|-------------------|-------------------------------------------------------------|
| `.env.local`      | variables d'environnement de production (lecture seule)     |
| `var/logs`        | base SQLite des logs du serveur                             |
| `var/sessions`    | sessions PHP : les utilisateurs restent connectés           |
| `var/sqlite`      | bases SQLite de l'application                               |
| `public/upload`   | fichiers envoyés par les utilisateurs                       |

!!! danger "Tout ce qui est écrit ailleurs est perdu"

    Si votre application enregistre des fichiers dans un autre dossier, ajoutez-le à `shared_dirs` **avant** le déploiement. Attention : définir `shared_dirs` remplace toute la liste, conservez les dossiers ci-dessus.

## Au quotidien

| Besoin                                    | Commande                                    |
|-------------------------------------------|---------------------------------------------|
| Déployer une nouvelle version             | `frankendeploy deploy prod`                 |
| Consulter les logs de l'application       | `frankendeploy logs prod`                   |
| Revenir à la version précédente           | `frankendeploy rollback prod`               |
| Ouvrir un terminal dans le conteneur      | `frankendeploy shell prod`                  |
| Lancer une commande `bin/edu` sur le serveur | `frankendeploy exec prod php bin/edu cache:clear` |
| Modifier une variable et redémarrer       | `frankendeploy env set prod CLE=valeur --reload` |

## L'image de production

Le `Dockerfile` du projet contient trois étapes (*stages*) :

| Stage             | Usage                                                              |
|-------------------|--------------------------------------------------------------------|
| `frankenphp_base` | socle commun : PHP 8.4, extensions, Composer                       |
| `frankenphp_prod` | production : utilisé par FrankenDeploy                            |
| `frankenphp_dev`  | développement : utilisé par `compose.yaml` (voir [L'application dans Docker](../installation/docker.md)) |

L'image de production diffère de l'image de développement :

- l'application est servie en **HTTP sur le port 8080** : le HTTPS est assuré par le Caddy du serveur
- elle s'exécute avec un utilisateur **non-root** (`app`)
- PHP utilise la configuration de production (`php.ini-production`, OPcache sans revalidation des fichiers)
- les dépendances de développement et le profiler Excimer ne sont pas installés
- la barre de debug est désactivée (`APP_ENV=prod`)
- le script `docker-entrypoint.sh` attend que la base de données soit disponible avant de démarrer

Vous pouvez construire et tester l'image de production sur votre poste :

```bash
docker build --target frankenphp_prod -t mon-app .
```

```bash
docker run --rm -p 8080:8080 mon-app
```

!!! warning "Ne supprimez pas et ne régénérez pas les fichiers Docker du projet"

    Si le `Dockerfile`, `docker-entrypoint.sh` ou `.dockerignore` sont absents, FrankenDeploy en génère de nouveaux, prévus pour Symfony : la construction de l'image échouera (commande `bin/console` introuvable).

    Pour la même raison, ne lancez pas `frankendeploy build`, `frankendeploy init --force` ni `frankendeploy dev` dans un projet Edu-Framework : ces commandes écrasent `Dockerfile`, `docker-entrypoint.sh`, `.dockerignore` et `compose.yaml`. Pour restaurer les fichiers :

    ```bash
    git checkout -- Dockerfile docker-entrypoint.sh .dockerignore compose.yaml
    ```

## Si vous rencontrez une erreur

### Le healthcheck échoue

FrankenDeploy affiche les 50 dernières lignes de logs du conteneur. Vérifiez que la route `healthcheck_path` répond sans authentification, et que les variables d'environnement sont définies (`frankendeploy env list prod`).

### Base de données injoignable

Le conteneur attend la base de données 60 secondes au démarrage. Vérifiez les variables `DB_*` avec `frankendeploy env list prod` et la `DATABASE_URL` d'une base gérée avec `frankendeploy exec prod printenv DATABASE_URL`.

### Le certificat HTTPS n'est pas délivré

L'enregistrement DNS du domaine doit pointer vers le serveur et les ports 80 et 443 doivent être ouverts. `frankendeploy doctor prod` vérifie ces deux points.
