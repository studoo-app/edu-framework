<?php

namespace Studoo\EduFramework\Scripts;

use Composer\Installer\PackageEvent;
use Composer\Script\Event;
class DockerPilot
{
    private const DOCKER_COMPOSE_MYSQL_RECIPE = './docker/docker-compose-mysql-5.yml';
    private const DOCKER_COMPOSE_MARIADB_RECIPE = './docker/docker-compose-maria-db.yml';

    public static function start(Event $event): void
    {
        $dockerComposeFileName = match ($event->getArguments()[0]??null) {
            'mysql' => self::DOCKER_COMPOSE_MYSQL_RECIPE,
            'maria-db' => self::DOCKER_COMPOSE_MARIADB_RECIPE,
            default => null
        };

        if($dockerComposeFileName){
            exec("docker compose -f $dockerComposeFileName  --env-file .env up -d");
        }else{
            echo "Invalid Db service. you must provide a database engine as arg.\n";
            echo "Example : composer edu:docker:db-service:start <engine>\n";
            echo 'Available engines are mysql, maria-db';
        }

    }

    public static function down(Event $event): void
    {
        $dockerComposeFileName = match ($event->getArguments()[0]??null) {
            'mysql' => self::DOCKER_COMPOSE_MYSQL_RECIPE,
            'maria-db' => self::DOCKER_COMPOSE_MARIADB_RECIPE,
            default => null
        };
        if($dockerComposeFileName){
            exec("docker compose -f $dockerComposeFileName  --env-file .env down");
        }else{
            echo "Invalid Db service. you must provide a database engine as arg.\n";
            echo "Example : composer edu:docker:db-service:start <engine>\n";
            echo 'Available engines are mysql, maria-db';
        }
    }
}
