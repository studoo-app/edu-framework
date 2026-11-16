# Migrer de la version v2.15 à v2.16

## Introduction
La version 2.16 du framework apporte le **cache applicatif** (`CacheService`) : mettre en cache des données issues de la base de données, dans des fichiers ou dans un serveur [Redis](https://redis.io){:target="_blank"}.

Elle est **rétro-compatible** : votre application fonctionne après la mise à jour du framework, **sans modifier votre code ni vos fichiers Docker**. Sans configuration, le cache utilise des fichiers (`CACHE_DRIVER=file`) et aucune connexion à Redis n'est ouverte. L'étape 3.3 est **optionnelle** : elle ajoute Redis à votre environnement Docker, pour suivre le tutoriel [Mettre des données en cache avec Redis](../build/cache-redis.md).

Lien sur les release notes : [Changelog](../changelog.md)

!!! info "La version de PHP ne change pas"

    Comme la v2.15, la v2.16 nécessite **PHP 8.4** minimum. Aucune nouvelle extension PHP n'est requise : le client Redis ([Predis](https://github.com/predis/predis){:target="_blank"}) est écrit en PHP et installé par Composer.

## Comment migrer ?
Pour migrer de la version 2.15 à la version 2.16, vous devez suivre les étapes suivantes :

1. **Analyser les changements** : lisez la section [Les changements](#les-changements) pour vérifier qu'aucun ne concerne votre projet.
2. **Versionner votre travail** : faites un commit avant de commencer, pour pouvoir revenir en arrière (`git status` ne doit rien afficher). La base de données n'est pas modifiée par cette migration.
3. Faire les actions suivantes :

3.1 **Mettre à jour le framework** :
```Bash
composer update studoo/edu-framework
```

Résultat :
```bash
[...]
Package operations: 1 install, 1 update, 0 removals
  - Installing predis/predis (v3.x.x): Extracting archive
  - Upgrading studoo/edu-framework (dev-main xxxxxxx => dev-main yyyyyyy): Extracting archive
[...]
```

La nouvelle dépendance `predis/predis` (le client Redis) est installée automatiquement.

3.2 **Vérifier la version et le cache** :
```Bash
php bin/edu check:config
```

La nouvelle section **Cache check** affiche le driver du cache :
```text
Cache check :
  [*] Driver du cache : file (variable CACHE_DRIVER)
```

La version `v2.16.0` est affichée dans la barre de debug (mode dev) et par la commande `php bin/edu`.

La migration est terminée : les étapes suivantes sont optionnelles.

3.3 **(Optionnel) Ajouter Redis à Docker** :

Dans le fichier `compose.yaml`, ajoutez les deux services suivants, au même niveau que le service `database` :

```yaml
  redis:
    container_name: ${APP_NAME}-redis
    # Serveur Redis : cache applicatif (CacheService) - https://redis.io
    image: redis:8-alpine
    ports:
      - "${REDIS_PORT:-6379}:6379"
    environment:
      REDIS_PASSWORD: ${REDIS_PASSWORD:-}
    # requirepass vide (défaut) => pas de mot de passe
    command: ["redis-server", "--appendonly", "yes", "--requirepass", "${REDIS_PASSWORD:-}"]
    volumes:
      - ./var/redis:/data
    healthcheck:
      test: ["CMD-SHELL", "redis-cli --no-auth-warning $${REDIS_PASSWORD:+-a \"$$REDIS_PASSWORD\"} ping | grep -q PONG"]
      interval: 5s
      timeout: 3s
      retries: 5
    restart: always

  redisinsight:
    container_name: ${APP_NAME}-redisinsight
    # Interface web pour explorer les clés Redis : http://localhost:5540
    image: redis/redisinsight:latest
    ports:
      - "${REDIS_UI_PORT:-5540}:5540"
    environment:
      RI_REDIS_HOST: redis
      RI_REDIS_PORT: 6379
      RI_REDIS_ALIAS: ${APP_NAME}
      RI_REDIS_DB: ${REDIS_DB:-0}
      RI_REDIS_PASSWORD: ${REDIS_PASSWORD:-}
    volumes:
      - redisinsight_data:/data
    depends_on:
      redis:
        condition: service_healthy
    restart: always
```

Dans le service `frankenphp`, ajoutez les variables du cache à la liste `environment:` : dans le réseau Docker, le serveur Redis est joignable par le nom du service (`redis`), et non par `127.0.0.1`.

```yaml
      CACHE_DRIVER: ${CACHE_DRIVER:-file}
      CACHE_PREFIX: ${CACHE_PREFIX:-edu:}
      CACHE_TTL: ${CACHE_TTL:-3600}
      REDIS_HOST: redis
      REDIS_PORT: 6379
      REDIS_PASSWORD: ${REDIS_PASSWORD:-}
      REDIS_DB: ${REDIS_DB:-0}
      REDIS_UI_PORT: ${REDIS_UI_PORT:-5540}
```

Enfin, déclarez le volume de Redis Insight dans la liste `volumes:` en fin de fichier :

```diff
 volumes:
   caddy_data:
   caddy_config:
++  redisinsight_data:
```

!!! tip "N'ajoutez pas de dépendance à Redis"

    N'ajoutez pas `redis` dans le `depends_on:` du service `frankenphp` : l'application doit pouvoir démarrer même si Redis est arrêté, ou si le port `6379` est déjà utilisé par un autre projet sur votre machine (changez alors `REDIS_PORT` dans le `.env`).

3.4 **(Optionnel) Activer Redis dans le `.env`** :

```diff
++ ## << Config Cache
++ CACHE_DRIVER=redis
++ CACHE_PREFIX=edu:
++ CACHE_TTL=3600
++ REDIS_HOST=127.0.0.1
++ REDIS_PORT=6379
++ REDIS_PASSWORD=
++ REDIS_DB=0
++ REDIS_UI_PORT=5540
++ ## >> Config Cache
```

Puis démarrez les services et vérifiez la connexion :

```Bash
docker compose up -d redis redisinsight
php bin/edu check:config
```

```text
Cache check :
  [*] Driver du cache : redis (variable CACHE_DRIVER)
  [*] Serveur Redis 127.0.0.1:6379 joignable (version 8.x)
```

3.5 **Enregistrer la migration** :
```Bash
git add composer.json composer.lock compose.yaml
git commit -m "chore: migration Edu Framework v2.16"
```

N'ajoutez pas le fichier `.env` : il contient vos réglages locaux et n'est jamais versionné. Si votre projet a un fichier `.env.example`, vous pouvez y recopier la section « Config Cache » de l'étape 3.4 pour vos coéquipiers.

## Les changements

### **La commande cache:clear**

`php bin/edu cache:clear` vide toujours le dossier `var/cache` (cache TWIG...), et **vide aussi le cache applicatif**. Deux options permettent de choisir :

| Commande | Ce qui est vidé |
|----------|-----------------|
| `php bin/edu cache:clear` | Le dossier `var/cache` et le cache applicatif |
| `php bin/edu cache:clear --files-only` | Uniquement le dossier `var/cache` (comportement de la v2.15) |
| `php bin/edu cache:clear --app-only` | Uniquement le cache applicatif (Exemple : les clés `edu:*` de Redis) |

Avec Redis, seules les clés qui portent le préfixe `CACHE_PREFIX` sont supprimées : un serveur Redis partagé n'est jamais vidé entièrement. Si Redis est arrêté, la commande affiche un avertissement mais ne s'arrête pas en erreur.

### **Les nouvelles variables d'environnement**

La v2.16 lit les variables `CACHE_DRIVER`, `CACHE_PREFIX`, `CACHE_TTL`, `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`, `REDIS_DB` et `REDIS_URL`. Elles sont toutes **optionnelles**.

!!! note "Votre projet utilise déjà ces noms ?"

    - Une variable `REDIS_URL` au format `redis://...` ou `rediss://...` est lue par le framework pour en déduire les variables `REDIS_*` manquantes (une variable déjà définie n'est jamais écrasée). Une `REDIS_URL` d'un autre format (Exemple : `tcp://hote:6379`) est **ignorée**.
    - Une `REDIS_URL` mal formée, ou une valeur invalide de `CACHE_DRIVER` ou de `CACHE_TTL`, n'empêche pas l'application de démarrer : l'erreur n'est signalée que lorsque le cache est utilisé, et par la commande `php bin/edu check:config`.

### **La barre de debug**

La barre de debug affiche un nouveau bloc **Cache** lorsque la page utilise le cache (lectures trouvées et absentes, écritures, temps). Le panneau du bloc **DEV** affiche le driver du cache et, avec Redis, le lien vers Redis Insight.

## Les nouveautés

### **Le cache applicatif** (v2.16)

| Fonctionnalité | Ce que l'on apprend |
|----------------|---------------------|
| `CacheService::getCache()` (`get`, `set`, `has`, `delete`, `clear`, `remember`) | Mettre en cache des données, choisir une durée de vie |
| Drivers `redis`, `file` et `none` (`CACHE_DRIVER`) | Changer de stockage par la configuration, sans modifier le code |
| `CacheService::getRedis()` | Utiliser les commandes Redis (`RENAME`, hash...) |
| Services Docker `redis` et `redisinsight` | Explorer les clés d'un cache Redis |

Pour découvrir le cache pas à pas : [Mettre des données en cache avec Redis](../build/cache-redis.md).
