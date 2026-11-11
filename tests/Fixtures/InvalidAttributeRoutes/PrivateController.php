<?php

namespace InvalidAttributeRoutes;

use Studoo\EduRoute\Attribute\Route;

/**
 * Controller de test : #[Route] sur une méthode privée (refusé par le moteur de routage)
 */
class PrivateController
{
    /**
     * Méthode privée : elle ne peut pas être appelée par le routeur
     * @return string
     */
    #[Route('/private')]
    private function index(): string
    {
        return 'private';
    }
}
