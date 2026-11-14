<?php

namespace AttributeRoutes;

use Studoo\EduFramework\Core\Controller\Request;
use Studoo\EduRoute\Attribute\Route;

/**
 * Controller de test : routes déclarées avec l'attribut #[Route]
 */
class HelloController
{
    /**
     * Route GET simple
     * @param Request $request Requête HTTP
     * @return string|null
     */
    #[Route('/hello', name: 'hello')]
    public function index(Request $request): string|null
    {
        return 'hello index';
    }

    /**
     * Route GET et POST avec un paramètre
     * @param Request $request Requête HTTP
     * @return string|null
     */
    #[Route('/hello/{name}', methods: ['GET', 'POST'], name: 'hello_name')]
    public function show(Request $request): string|null
    {
        return 'hello ' . $request->get('name');
    }
}
