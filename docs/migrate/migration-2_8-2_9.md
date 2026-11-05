# Migrer de la version v2.8 à v2.9

## Introduction
La version 2.9 du framework apporte deux nouveautés : le **déploiement de l'application sur un serveur** (Debian ou Ubuntu) avec la commande `make:deploy`, et le **flamegraph intégré** au profiler de code. \
Elle est **rétro-compatible** : votre application fonctionne après la mise à jour du framework, sans modifier votre code (lisez toutefois [le changement du chargement des variables d'environnement](#le-chargement-des-variables-denvironnement)). Les autres étapes de cette page préparent votre projet au déploiement et aux nouveaux fichiers Docker.
Lien sur les release notes : [v2.9](../changelog.md#v290-21102026)

!!! info "La version de PHP ne change pas"

    Comme la v2.8, la v2.9 nécessite **PHP 8.4** minimum. Si votre projet fonctionne en v2.8, votre version de PHP convient.

!!! tip "Vous venez d'une version plus ancienne ?"

    Faites d'abord la [migration de la version 2.3 à la version 2.8](migration-2_3-2_8.md), puis suivez cette page.

## Comment migrer ?
Pour migrer de la version 2.8 à la version 2.9, vous devez suivre les étapes suivantes :

1. **Analyser les changements** : lisez les sections [Les changements](#les-changements) et [Les nouveautés](#les-nouveautes) pour comprendre leur impact sur votre projet.
2. **Versionner votre travail** : faites un commit avant de commencer, pour pouvoir revenir en arrière (`git status` ne doit rien afficher). La base de données n'est pas modifiée par cette migration.
3. Faire les actions suivantes :

3.1 **Mettre à jour le framework** :
```Bash
composer update studoo/edu-framework
```

Résultat :
```bash
[...]
Package operations: 0 installs, 1 update, 0 removals
  - Upgrading studoo/edu-framework (dev-main xxxxxxx => dev-main yyyyyyy): Extracting archive
[...]
```

Les identifiants de commit `xxxxxxx` et `yyyyyyy` varient : la version installée se vérifie à l'étape 3.5.

3.2 **Mettre à jour les fichiers Docker** :

La v2.9 découpe le `Dockerfile` en trois étapes (*stages*) : une image de **développement**, identique à celle de la v2.8, et une image de **production**, utilisée pour le déploiement. L'image de production a besoin de deux nouveaux fichiers : `docker-entrypoint.sh` et `.dockerignore`.

Copiez les trois fichiers depuis le framework installé par Composer à l'étape 3.1 (sous Windows, dans un terminal git bash) :

```bash
cp vendor/studoo/edu-framework/Dockerfile vendor/studoo/edu-framework/docker-entrypoint.sh vendor/studoo/edu-framework/.dockerignore .
```

!!! tip "Vous avez personnalisé votre Dockerfile ?"

    La copie remplace votre `Dockerfile`. Retrouvez l'ancienne version, enregistrée par le commit de l'étape 2, avec `git show HEAD:Dockerfile`, puis reportez vos modifications dans le stage `frankenphp_base` (extensions et paquets communs aux deux images) ou dans le stage `frankenphp_dev` (outils de développement uniquement). Le nom du stage `frankenphp_prod` ne doit pas changer : FrankenDeploy construit l'image avec ce nom.

Dans le fichier `compose.yaml`, le service `frankenphp` doit construire l'image de développement et transmettre l'argument `EDU_PROFILER` (profiler de code). Dans les projets créés avec le skeleton, il contient la forme courte `build: .` sur une seule ligne : remplacez-la entièrement par le bloc `build:` ci-dessous, au même niveau d'indentation que `container_name:`.

```diff
  frankenphp:
    container_name: ${APP_NAME}-http-server
--  build: .
++  build:
++    context: .
++    # Stage de développement du Dockerfile (frankenphp_prod sert au déploiement)
++    target: frankenphp_dev
++    args:
++      EDU_PROFILER: ${EDU_PROFILER_BUILD:-1}
```

!!! warning "N'ajoutez pas de lignes sous `build: .`"

    `target` et `args` ne peuvent pas être ajoutés sous la forme courte `build: .` : Docker Compose refuse le fichier (`did not find expected key`). Si votre clé `build:` contient déjà `context` et `args`, ajoutez seulement la ligne `target`. Sans le bloc `args`, la variable `EDU_PROFILER_BUILD` du `.env` est sans effet.

Reconstruisez l'image de développement si vous utilisez le [flux 100 % Docker](../installation/docker.md) :

```Bash
docker compose up -d --build
```

3.3 **Mettre à jour le fichier `.gitignore`** :

Le fichier `composer.lock` **doit** maintenant être versionné : l'image de production installe exactement les versions qu'il contient, et FrankenDeploy n'envoie au serveur que les fichiers suivis par git. Supprimez la ligne `composer.lock` du `.gitignore`.

La v2.9 lit aussi le fichier `.env.local`, qui contient les secrets de production : il ne doit **jamais** être versionné. Vérifiez que les lignes `.env.local` et `.env.*.local` sont présentes (c'est déjà le cas dans les projets créés avec le skeleton), et ajoutez-les si elles manquent.

```diff
  .env
  .env.local
  .env.*.local
--composer.lock
```

Puis ajoutez le fichier `composer.lock` au dépôt :

```Bash
git add .gitignore composer.lock
```

3.4 **Fixer les fins de ligne des scripts shell (Windows)** :

!!! danger "Le conteneur de production ne démarre pas"

    Sous Windows, git peut convertir les fins de ligne du fichier `docker-entrypoint.sh` au format Windows (CRLF). Le conteneur de production refuse alors de démarrer avec l'erreur `exec /usr/local/bin/docker-entrypoint: no such file or directory`.

Créez le fichier `.gitattributes` à la racine du projet, pour que git conserve toujours les fins de ligne Linux (LF) des scripts shell :

```text
*.sh text eol=lf
```

Puis normalisez les fichiers déjà présents :

```Bash
git add .gitattributes
git add --renormalize .
```

Cette étape est sans effet sous macOS et Linux, mais protège les membres de l'équipe qui travaillent sous Windows.

3.5 **Vider le cache et vérifier la version** :
```Bash
php bin/edu cache:clear
php bin/edu --version
```

Résultat :
```bash
EduFramework v2.9.0
```

La nouvelle commande `make:deploy` apparaît dans la liste des commandes :

```Bash
php bin/edu list make
```

Enfin, versionnez la migration :

```Bash
git add Dockerfile docker-entrypoint.sh .dockerignore compose.yaml
git commit -m "Migration vers Edu-Framework v2.9"
```

## Les changements

### **Le chargement des variables d'environnement**
Les variables d'environnement sont maintenant chargées par la méthode `ConfigCore::loadEnv()`, utilisée par l'application (`public/index.php`) et par la commande `bin/edu`. Elles sont lues dans cet ordre (la première valeur trouvée est conservée) :

| Priorité | Source                                                   | Usage                                   |
|----------|----------------------------------------------------------|-----------------------------------------|
| 1        | variables d'environnement du processus (Docker, shell)   | `APP_ENV`, `DATABASE_URL` en production |
| 2        | fichier `.env.local` (nouveau)                           | secrets de production, jamais versionné |
| 3        | fichier `.env`                                           | configuration de développement          |

Conséquences pour votre projet :

- les fichiers `.env` et `.env.local` sont **optionnels** : l'application et `bin/edu` démarrent sans eux (en production, l'image Docker ne contient pas de `.env`) ;
- une variable définie dans votre terminal ou par Docker **remplace** celle du `.env` (Exemple : `APP_ENV=prod php bin/edu start` démarre en mode production) ;
- avec `php bin/edu start`, les variables du `.env` et du `.env.local` sont lues au démarrage et transmises au serveur PHP : **après une modification de ces fichiers, arrêtez le serveur (++ctrl+c++) puis relancez `php bin/edu start`** (en v2.8, la modification était prise en compte dès la requête suivante) ;
- `ConfigCore::getEnv()` renvoie une chaîne vide pour une variable absente, sans avertissement PHP. Utilisez `ConfigCore::existEnv()` pour savoir si une variable est définie.

!!! note "La variable DATABASE_URL"

    Si la variable `DATABASE_URL` est définie (format `mysql://utilisateur:motdepasse@hote:3306/base`), le framework en déduit les variables `DB_HOST_STATUS`, `DB_TYPE`, `DB_HOST`, `DB_SOCKET`, `DB_USER`, `DB_PASSWORD` et `DB_NAME` **qui ne sont pas déjà définies**. Les variables `DB_*` de votre `.env` restent donc prioritaires.

    Une `DATABASE_URL` MySQL, MariaDB ou PostgreSQL mal formée arrête l'application avec le message « La variable DATABASE_URL est invalide ». Une `DATABASE_URL` d'un autre type (Exemple : `sqlite:///...` d'un projet Symfony) est ignorée.

### **La commande bin/edu**
`bin/edu` charge les variables d'environnement même sans fichier `.env`. Si `DB_HOST_STATUS=true` est défini (dans le `.env`, le `.env.local` ou l'environnement), la commande se connecte à la base de données au démarrage, comme en v2.8 : la base doit donc être démarrée.

### **Le Dockerfile multi-stage**
Le `Dockerfile` contient maintenant trois étapes :

| Stage             | Usage                                                              |
|-------------------|--------------------------------------------------------------------|
| `frankenphp_base` | socle commun : PHP 8.4, extensions, Composer                       |
| `frankenphp_prod` | production : utilisé par FrankenDeploy (voir [Déployer son application](../deploy/index.md#limage-de-production)) |
| `frankenphp_dev`  | développement : utilisé par `compose.yaml`, identique à l'image v2.8 |

L'image de développement ne change pas : le flux 100 % Docker et les commandes `docker compose` restent identiques.

## Les nouveautés

### **Déployer son application** (v2.9)
La commande `php bin/edu make:deploy` génère le fichier `frankendeploy.yaml`, qui décrit le déploiement de l'application sur un serveur Debian ou Ubuntu avec [FrankenDeploy](https://yoanbernabeu.github.io/frankendeploy/){:target="_blank"} : HTTPS automatique, base de données MariaDB gérée, mise à jour sans coupure de service et retour à la version précédente.

```Bash
php bin/edu make:deploy --domain=mon-app.fr
```

Pour plus de détails, consulter la page [Déployer son application](../deploy/index.md).

### **Le flamegraph intégré au profiler** (v2.9)
L'onglet **Code** du profiler affiche le profil d'une requête sans fichier à télécharger, avec trois nouvelles vues :

- **Flamegraph** : les appels regroupés, le plus coûteux à gauche ;
- **Chronologique** : l'ordre des appels, avec un axe du temps ;
- **Comparer** : le flamegraph différentiel de deux requêtes profilées (en rouge ce qui est plus lent, en bleu ce qui est plus rapide).

Le bloc **Code** de la barre de debug affiche aussi un **mini-flamegraph** : un clic l'ouvre en grand dans le profiler.

Pour plus de détails, consulter les sections [Le flamegraph intégré](../boost/debug-bar.md#le-flamegraph-integre) et [Comparer deux requêtes](../boost/debug-bar.md#comparer-deux-requetes).
