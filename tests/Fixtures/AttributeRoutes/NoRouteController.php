<?php

namespace AttributeRoutes;

/**
 * Controller de test sans attribut #[Route] : il est ignoré
 */
class NoRouteController
{
    /**
     * Méthode sans route
     * @return string
     */
    public function index(): string
    {
        return 'no route';
    }
}
