#!/bin/sh
# Edu-Framework - point d'entrée de l'image de production (stage frankenphp_prod)
#
# Attend que la base de données accepte les connexions, puis démarre FrankenPHP.
# La base est décrite par DATABASE_URL (base gérée par FrankenDeploy) ou par les
# variables DB_* (DB_HOST_STATUS=true), passées par Docker ou le fichier .env.local.
set -e

log() {
    echo "[Edu-Framework] $1"
}

# Hôte et port de la base de données, calculés par le framework lui-même
# (même lecture des variables que l'application : environnement, .env.local, .env)
db_target() {
    php -r '
        require "/app/vendor/autoload.php";
        use Studoo\EduFramework\Core\ConfigCore;
        new ConfigCore([]);
        ConfigCore::loadEnv("/app/");
        if (ConfigCore::getEnv("DB_HOST_STATUS") !== "true"
            || in_array(ConfigCore::getEnv("DB_TYPE"), ["mysql", "mariadb", "pgsql"], true) === false
            || ConfigCore::getEnv("DB_HOST") === "") {
            exit;
        }
        $port = ConfigCore::getEnv("DB_SOCKET");
        if ($port === "") {
            $port = ConfigCore::getEnv("DB_TYPE") === "pgsql" ? "5432" : "3306";
        }
        echo ConfigCore::getEnv("DB_HOST") . " " . $port;
    ' 2>/dev/null || true
}

wait_for_database() {
    target=$(db_target)
    if [ -z "$target" ]; then
        return 0
    fi

    host=${target% *}
    port=${target#* }
    log "Attente de la base de données $host:$port..."
    attempts=0
    until php -r 'exit(@fsockopen($argv[1], (int) $argv[2], $e, $s, 2) ? 0 : 1);' "$host" "$port"; do
        attempts=$((attempts + 1))
        if [ "$attempts" -ge 60 ]; then
            log "Base de données injoignable"
            exit 1
        fi
        sleep 1
    done
    log "Base de données prête"
}

wait_for_database

log "Démarrage de FrankenPHP"
exec docker-php-entrypoint "$@"
