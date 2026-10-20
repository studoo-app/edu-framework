<?php

/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Profiler;

/**
 * Class NullDriver
 * Driver utilisé quand aucune extension de profilage n'est installée :
 * il ne collecte rien et n'est jamais disponible.
 *
 * @package Studoo\EduFramework\Core\Profiler
 */
class NullDriver implements ProfilerDriverInterface
{
    /**
     * @return string Nom du driver (none)
     */
    public function name(): string
    {
        return 'none';
    }

    /**
     * @return bool Toujours false : aucune extension de profilage n'est installée
     */
    public function isAvailable(): bool
    {
        return false;
    }

    /**
     * Ne fait rien : aucune extension de profilage
     *
     * @return void
     */
    public function start(): void
    {
    }

    /**
     * @return array<string, mixed> Profil vide
     */
    public function stop(): array
    {
        return [];
    }
}
