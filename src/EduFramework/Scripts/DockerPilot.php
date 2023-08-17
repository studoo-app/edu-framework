<?php

namespace Studoo\EduFramework\Scripts;

use Composer\Installer\PackageEvent;
use Composer\Script\Event;
use Studoo\EduFramework\Scripts\Exception\DockerPilotInvalidArgumentException;

class DockerPilot
{
    private const DOCKER_COMPOSE_MYSQL_RECIPE = './docker/docker-compose-mysql-5.yml';
    private const DOCKER_COMPOSE_MARIADB_RECIPE = './docker/docker-compose-maria-db.yml';
    private const DOCKER_START_INSTRUCTION = "up -d";
    private const DOCKER_DOWN_INSTRUCTION = "down";


    private static function buildCommand(Event $event, string $instruction): ?string
    {
        $dockerComposeFileName = match($event->getArguments()[0] ?? null) {
            'mysql' => self::DOCKER_COMPOSE_MYSQL_RECIPE,
            'maria-db' => self::DOCKER_COMPOSE_MARIADB_RECIPE,
            default => null
        };

        return isset($dockerComposeFileName) ?
            "docker compose -f $dockerComposeFileName  --env-file .env $instruction" :
            null;
    }

    /**
     * @throws DockerPilotInvalidArgumentException
     */
    public static function start(Event $event): void
    {
        $command = self::buildCommand($event,self::DOCKER_START_INSTRUCTION);

        if(!isset($command)){
            throw new DockerPilotInvalidArgumentException();
        }
        exec($command);

    }

    /**
     * @throws DockerPilotInvalidArgumentException
     */
    public static function down(Event $event): void
    {
        $command = self::buildCommand($event,self::DOCKER_DOWN_INSTRUCTION);

        if(!isset($command)){
            throw new DockerPilotInvalidArgumentException();
        }
        exec($command);
    }

}
