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

    /**
     * @param Event $event
     * @param string $instruction
     * @return string|null
     * @throws DockerPilotInvalidArgumentException
     */
    private static function buildCommand(Event $event, string $instruction): ?string
    {
        $dockerComposeFileName = match ($event->getArguments()[0] ?? null) {
            'mysql' => self::DOCKER_COMPOSE_MYSQL_RECIPE,
            'maria-db' => self::DOCKER_COMPOSE_MARIADB_RECIPE,
            default => null
        };

        if(!isset($dockerComposeFileName)){
            throw new DockerPilotInvalidArgumentException();
        }

        return "docker compose -f $dockerComposeFileName  --env-file .env $instruction";
    }

    /**
     * @param Event $event
     * @return void
     * @throws DockerPilotInvalidArgumentException
     */
    public static function start(Event $event): void
    {
        $command = self::buildCommand($event,self::DOCKER_START_INSTRUCTION);
        exec($command);

    }

    /**
     * @param Event $event
     * @return void
     * @throws DockerPilotInvalidArgumentException
     */
    public static function down(Event $event): void
    {
        $command = self::buildCommand($event,self::DOCKER_DOWN_INSTRUCTION);
        exec($command);
    }

}
