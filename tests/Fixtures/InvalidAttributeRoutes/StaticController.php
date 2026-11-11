<?php

namespace InvalidAttributeRoutes;

use Studoo\EduRoute\Attribute\Route;

/**
 * Controller de test : #[Route] sur une méthode statique (refusé par le moteur de routage)
 */
class StaticController
{
    /**
     * Méthode statique : le routeur appelle des méthodes d'instance
     * @return string
     */
    #[Route('/static')]
    public static function index(): string
    {
        return 'static';
    }
}
