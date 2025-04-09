<?php

/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Controller;

use Studoo\EduFramework\Core\Exception\ErrorControllerException;

trait BuildControllerTrait
{
    /**
     * Permet de construire le controller
     * @param string $controller Nom du controller
     * @return ControllerInterface Retourne l'objet du controller
     * @throws ErrorControllerException
     */
    public function buildController(string $controller): ControllerInterface
    {
        // On vérifie que le controller existe et qu'il implémente l'interface ControllerInterface
        if (class_exists($controller) === false
            || in_array(ControllerInterface::class, class_implements($controller), true) === false) {
            throw new ErrorControllerException();
        }

        // Création de l'objet du controller en respectant le contrat de l'interface "ControllerInterface"
        return new $controller();
    }
}
