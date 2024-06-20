# Migrer de la version v1.2 à v2.0

## Introduction
La version 2.0 du framework apporte de nombreuses améliorations et corrections de bugs. \
Je vous invite à lire attentivement les changements apportés pour migrer votre projet de la version 1.2 à la version 2.0.
Lien sur la release note : [Release note 2.0](fr-release-edu.md)

## Comment migrer ?
Pour migrer de la version 1.2 à la version 2.0, vous devez suivre les étapes suivantes :

1. **Analyser les changements** : Analysez les changements apportés par la nouvelle version pour comprendre leur impact sur votre projet.
2. **Sauvegarder vos données** : Assurez-vous de sauvegarder vos données avant de procéder à la migration pour éviter toute perte.
3. Faire les actions suivantes : 

3.1 **Mettre à jour le fichier composer** :
```
  "require": {
    "php": ">=8.1",
    "ext-mbstring": "*",
--    "studoo/edu-framework": "1.2.x-dev"
++    "studoo/edu-framework": "2.0.x-dev"
  },
```

3.2 **Mettre à jour le framework** :
```Bash
composer update studoo/edu-framework
```

Résultat :
```
[...]
Package operations: 0 installs, 1 update, 0 removals
  - Downloading studoo/edu-framework (2.0.x-dev xxxxx)
  - Upgrading studoo/edu-framework (1.2.x-dev ef385b8 => 2.0.x-dev xxxxx): Extracting archive
[...]
```

3.3 **Version le framework** :
```Bash
 php bin/edu --version
```

Résultat :
```
EduFramework v2.0.x-xxxx
```

## Les changements
Les changements sont nombreux entre la version 1.2 et la version 2.0. \
Voici les principaux changements à prendre en compte :

### **Docker**
Dans la version 1.2, les services était démarré avec la commande `composer edu:docker:db-service:start mysql`. \
En version 2.0, les services sont démarrés avec la commande `docker compose up -d`.

Voici les actions à réaliser pour migrer de la version 1.2 à la version 2.0 :

1. **Arrêter les services** : (si sont démarrés)
```Bash
composer edu:docker:db-service:stop mysql
```

2. **Téléchargement du nouveau fichier Docker** :

<tabs>
    <tab title="WINDOWS">
        <warning>
            <p>
                Ouvrir un terminal git bash à la racine de votre projet
            </p>
        </warning>
        Pour télécharger le fichier compose, suivre les instructions :
        <code-block lang="Bash">
        curl -o compose.yaml https://raw.githubusercontent.com/studoo-app/edu-framework/2.0/compose.yaml
        </code-block>
        Si vous rencontrez une erreur `curl: (35) schannel: next InitializeSecurityContext failed: Unknown error (0x80092012) - The revocation function was unable to check revocation for the certificate.`
        <code-block lang="Bash">
        curl --ssl-no-revoke -o compose.yaml https://raw.githubusercontent.com/studoo-app/edu-framework/2.0/compose.yaml
        </code-block>
    </tab>
    <tab title="MAC OS">
        <warning>
            <p>
                Ouvrir un terminal à la racine de votre projet
            </p>
        </warning>
        Pour télécharger le fichier compose, suivre les instructions :
        <code-block lang="Bash">
        curl -sS https://raw.githubusercontent.com/studoo-app/edu-framework/2.0/compose.yaml -o compose.yaml
        </code-block>
    </tab>
</tabs>

3. **Modifier le nouveau fichier Docker** :

Vous pouvez modifier le fichier `compose.yaml` pour ajuster les paramètres à votre environnement.

```
    environment:
      MYSQL_DATABASE: app_db
--    MYSQL_ALLOW_EMPTY_PASSWORD: 'yes'
++    #MYSQL_ALLOW_EMPTY_PASSWORD: 'yes'
--    #MYSQL_ROOT_PASSWORD: root
++    MYSQL_ROOT_PASSWORD: root
      #MYSQL_USER: app_db_user
      #MYSQL_PASSWORD: app_db_password
```

> **Note** : Les commentaires sont ajoutés pour vous aider à comprendre les modifications.
> Bien respecté la syntaxe YAML pour éviter les erreurs. (indentation, etc.)
> {style="info"}

4. **Démarrer les services** :

```Bash
docker compose up -d
```

5. **Supprimer les anciens dossier Docker** :

> Ouvrir un terminal git bash à la racine de votre projet
> {style="warning"}

```Bash
rm -fr docker
```

6. **Supprimer les lignes scripts dans composer** :

> Ouvrir le fichier `composer.json` et supprimer les lignes suivantes :

```
  "scripts": {
    "edu:start": [
      "Composer\\Config::disableProcessTimeout",
      "php -S localhost:8042 -t public"
    ],
    "edu:init": [
      "php -r \"file_exists('.env') || copy('.env.example', '.env');\""
    ],
--  "edu:docker:db-service:start": "Studoo\\EduFramework\\Scripts\\DockerPilot::start",
--  "edu:docker:db-service:down": "Studoo\\EduFramework\\Scripts\\DockerPilot::down"
  },
  "scripts-descriptions": {
    "edu:init": "Create env file",
    "edu:start": "Start local server",
--  "edu:docker:db-service:start": "Start docker related database service [args : mysql / maria-db]",
--  "edu:docker:db-service:down": "Stop and remove docker related database service [args : mysql / maria-db]"
  }
```

### **Barre de debug**

Le fichier de configuration d'environnement a été modifié pour être plus lisible et plus facile à comprendre.

Voici la liste des variables :

 - La variable `APP_ENV` \
Cette variable a pour fonction d'activer des fonctionnalités de debug

| Valeur | Description |
|--------|-------------|
| dev    | Mode développement |
| prod   | Mode production |

Exemple :
```Bash
APP_ENV=dev
```

 - La variable `DB_TYPE` \
Cette variable a pour fonction de définir le type de base de données

| Valeur  | Description             |
|---------|-------------------------|
| mysql   | Base de données MySQL   |
| mariadb | Base de données Mariadb |

Exemple :
```Bash
DB_TYPE=mysql
```

## Les nouveautés

### **Nouvelles commandes**
De nouvelles commandes ont été ajoutées pour faciliter le développement de votre application.

- `php bin/edu make:api` : Pour générer un controller de type API
- `php bin/edu make:command` : Pour générer une commande console
