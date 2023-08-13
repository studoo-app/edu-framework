<?php
/*
 * Ce fichier fait partie du Studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Controller;

/**
 * Interface ControllerInterface
 * Elle permet de définir les méthodes que doivent implémenter les controllers
 * Et d'appeler la méthode execute() de chaque controller sans savoir le nom de la classe
 */
interface ControllerInterface
{
    /**
     * La méthode execute() est appelée par le dispatcher
     * Elle permet d'exécuter le controller et de retourner le résultat
     *
     * @param Request $request La requête HTTP
     * @return string|null
     */
    public function execute(Request $request): string|null;
}
