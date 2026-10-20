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
 * Interface ProfilerDriverInterface
 * Contrat d'un driver du profiler de code : il encapsule l'extension PHP
 * de profilage (Excimer aujourd'hui) pour que le framework n'en dépende pas.
 * Le profilage est démarré et arrêté explicitement à chaque requête HTTP,
 * ce qui fonctionne aussi en mode worker de FrankenPHP (les extensions n'y
 * sont pas réinitialisées entre deux requêtes).
 *
 * @package Studoo\EduFramework\Core\Profiler
 */
interface ProfilerDriverInterface
{
    /**
     * @return string Nom du driver, enregistré avec le profil (Exemple : excimer)
     */
    public function name(): string;

    /**
     * @return bool Indique si l'extension nécessaire au driver est chargée
     */
    public function isAvailable(): bool;

    /**
     * Démarre le profilage de la requête en cours
     *
     * @return void
     */
    public function start(): void;

    /**
     * Arrête le profilage et retourne les données collectées :
     * - period_ms : période d'échantillonnage en millisecondes
     * - samples : nombre d'échantillons
     * - functions : agrégat par fonction (self, inclusive, file, line, class, function)
     * - speedscope : piles d'appels au format Speedscope (flamegraph)
     *
     * @return array<string, mixed>
     */
    public function stop(): array;
}
