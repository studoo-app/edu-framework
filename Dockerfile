# Edu-Framework servi par FrankenPHP (PHP + Caddy : HTTPS et HTTP/3 automatiques)
# https://frankenphp.dev/docs/docker/
#
# Edu-Framework exige PHP >= 8.4 (voir composer.json et public/index.php)
#
# Image multi-stage :
#   frankenphp_base  -> socle commun (extensions PHP, Composer)
#   frankenphp_prod  -> image de production (déploiement avec FrankenDeploy,
#                       voir frankendeploy.yaml et la doc "Déployer son application")
#   frankenphp_dev   -> image de développement (compose.yaml), stage par défaut
#
#   docker build --target frankenphp_prod -t mon-app .   => image de production
#   docker compose build                                 => image de développement

###################
# Socle commun
###################
FROM dunglas/frankenphp:php8.4 AS frankenphp_base

# Outils CLI : curl (tests d'API et healthcheck de l'image de production)
# et unzip (décompression propre des archives Composer)
RUN apt-get update \
    && apt-get install -y --no-install-recommends curl unzip \
    && rm -rf /var/lib/apt/lists/*

# Extensions PHP requises par Edu-Framework
# Prérequis officiels : https://studoo-app.github.io/edu-framework/installation/prerequis.html
# (vérifiés par la commande `php bin/edu check:config`)
#   pdo_sqlite : service de logs, barre de debug et profiler (requis depuis la v2.3.0)
#   pdo_mysql  : connexion MySQL / MariaDB (DatabaseService)
#   mbstring   : manipulation de chaînes UTF-8 (Twig, Symfony)
#   openssl    : chiffrement, HTTPS
# Complémentaires utiles :
#   zip, intl : archives, internationalisation
#   opcache   : performances
# Note : certaines sont déjà compilées dans l'image de base ; le script les
# détecte et les ignore, mais on les déclare pour garantir les prérequis du
# framework même si l'image de base évolue.
RUN install-php-extensions \
    pdo_sqlite \
    pdo_mysql \
    mbstring \
    openssl \
    zip \
    intl \
    opcache

# Composer (depuis l'image officielle)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1

###################
# Production
###################
# Contrat FrankenDeploy : stage nommé frankenphp_prod, HTTP sur le port 8080
# (le HTTPS est assuré par le Caddy frontal du serveur), utilisateur non-root
# "app" (uid 1000) et script docker-entrypoint
FROM frankenphp_base AS frankenphp_prod

ENV APP_ENV=prod
# HTTP simple sur le port 8080 (voir Caddyfile)
ENV SERVER_NAME=:8080

# Configuration PHP de production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
# - OPcache : le code ne change pas sans redéploiement (pas de revalidation)
# - Sessions : stockées dans var/sessions, dossier partagé entre les releases
#   (les utilisateurs restent connectés après un déploiement)
COPY <<EOF $PHP_INI_DIR/conf.d/app.prod.ini
expose_php = Off
opcache.validate_timestamps = 0
opcache.memory_consumption = 256
opcache.max_accelerated_files = 20000
opcache.interned_strings_buffer = 16
realpath_cache_size = 4096K
realpath_cache_ttl = 600
session.save_path = /app/var/sessions
EOF

# Utilisateur non-root qui exécute l'application
ARG UID=1000
ARG GID=1000
RUN groupadd --gid ${GID} app \
    && useradd --uid ${UID} --gid ${GID} --create-home --shell /bin/bash app

# Dépendances PHP sans les outils de développement (couche mise en cache tant
# que composer.json / composer.lock ne changent pas)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-autoloader --no-scripts --no-interaction --no-progress --prefer-dist

# Sources de l'application (le .dockerignore exclut .env, var/, vendor/, docs...)
COPY --chown=${UID}:${GID} . ./
RUN composer dump-autoload --optimize --no-dev

# Configuration du serveur
COPY Caddyfile /etc/frankenphp/Caddyfile

# Dossiers d'exécution accessibles en écriture par l'utilisateur app
# (var/logs, var/sessions, var/sqlite et public/upload sont des dossiers
# partagés montés par FrankenDeploy, voir frankendeploy.yaml)
RUN mkdir -p var/cache var/logs var/sessions var/sqlite public/upload \
    && chown -R app:app var public/upload /data /config

# Attend la base de données puis démarre FrankenPHP
COPY --chmod=755 docker-entrypoint.sh /usr/local/bin/docker-entrypoint
ENTRYPOINT ["docker-entrypoint"]
CMD ["--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]

# Sonde applicative : vérifie que l'application répond, pas seulement Caddy
# (même URL que healthcheck_path dans frankendeploy.yaml)
HEALTHCHECK --start-period=60s CMD curl -f http://localhost:8080/ || exit 1

USER app

EXPOSE 8080

###################
# Développement (stage par défaut, utilisé par compose.yaml)
###################
FROM frankenphp_base AS frankenphp_dev

# Profiler de code de la barre de debug (Excimer, profiler par échantillonnage)
# Installé uniquement pour l'image de développement :
#   docker compose build --build-arg EDU_PROFILER=0   => image sans profiler
# Doc : https://www.mediawiki.org/wiki/Excimer
ARG EDU_PROFILER=1
RUN if [ "$EDU_PROFILER" = "1" ]; then install-php-extensions excimer; fi

# Installation des dépendances PHP (couche mise en cache tant que
# composer.json / composer.lock ne changent pas)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Copie des sources (en dev, compose.yaml surcharge /app par un volume)
COPY . /app

# Configuration du serveur (compose.yaml monte aussi ce fichier en dev)
COPY Caddyfile /etc/frankenphp/Caddyfile

# HTTP, HTTPS, HTTP/3
EXPOSE 80 443 443/udp
