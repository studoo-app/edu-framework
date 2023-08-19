<?php
/*
 * Ce fichier fait partie du Studoo
 *
 * @author Julien Pechberty
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Scripts;

use Composer\Installer\PackageEvent;
use Composer\Script\Event;
use Studoo\EduFramework\Scripts\Exception\DockerPilotInvalidArgumentException;

/**
 * Class DockerPilot
 * @package Studoo\EduFramework\Scripts
 */
class DockerPilot
{
    private const DOCKER_COMPOSE_MYSQL_RECIPE = './docker/docker-compose-mysql-5.yml';
    private const DOCKER_COMPOSE_MARIADB_RECIPE = './docker/docker-compose-maria-db.yml';
    private const DOCKER_START_INSTRUCTION = "up -d";
    private const DOCKER_DOWN_INSTRUCTION = "down";

    /**
     * @param Event $event Nom de l'événement
     * @param string $instruction (start|down)
     * @return string|null
     * @throws DockerPilotInvalidArgumentException
     */
    private static function buildCommand(Event $event, string $instruction): ?string
    {
        $recipe = match ($event->getArguments()[0] ?? null) {
            'mysql' => self::DOCKER_COMPOSE_MYSQL_RECIPE,
            'maria-db' => self::DOCKER_COMPOSE_MARIADB_RECIPE,
            default => null
        };

        if (isset($recipe) === false) {
            throw new DockerPilotInvalidArgumentException();
        }

        return "docker compose -f $recipe  --env-file .env $instruction";
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
     * @param Event $event Nom de l'événement
     * @return void
     * @throws DockerPilotInvalidArgumentException
     */
    public static function down(Event $event): void
    {
        $command = self::buildCommand($event,self::DOCKER_DOWN_INSTRUCTION);
        exec($command);
    }

}
