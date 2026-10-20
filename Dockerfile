# Edu-Framework servi par FrankenPHP (PHP + Caddy : HTTPS et HTTP/3 automatiques)
# https://frankenphp.dev/docs/docker/
#
# Edu-Framework exige PHP >= 8.4 (voir composer.json et public/index.php)
FROM dunglas/frankenphp:php8.4

# Outils CLI : curl (tests d'API, comme pour le poste de travail local)
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

# Profiler de code de la barre de debug (Excimer, profiler par échantillonnage)
# Installé uniquement pour l'image de développement :
#   docker compose build --build-arg EDU_PROFILER=0   => image sans profiler
# Doc : https://www.mediawiki.org/wiki/Excimer
ARG EDU_PROFILER=1
RUN if [ "$EDU_PROFILER" = "1" ]; then install-php-extensions excimer; fi

# Composer (depuis l'image officielle)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Installation des dépendances PHP (couche mise en cache tant que
# composer.json / composer.lock ne changent pas)
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Copie des sources (en dev, compose.yaml surcharge /app par un volume)
COPY . /app

# Configuration du serveur (compose.yaml monte aussi ce fichier en dev)
COPY Caddyfile /etc/frankenphp/Caddyfile

# HTTP, HTTPS, HTTP/3
EXPOSE 80 443 443/udp
