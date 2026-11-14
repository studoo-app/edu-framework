<?php

namespace AttributeRoutes\Admin;

use Studoo\EduFramework\Core\Controller\Request;
use Studoo\EduRoute\Attribute\Route;
use Studoo\EduRoute\Attribute\RouteGroup;

/**
 * Controller de test : préfixe commun avec l'attribut #[RouteGroup]
 */
#[RouteGroup('/admin', namePrefix: 'admin_')]
class ProductController
{
    /**
     * Route /admin/product/{id} nommée admin_product
     * @param Request $request Requête HTTP
     * @return string|null
     */
    #[Route('/product/{id:\d+}', name: 'product')]
    public function edit(Request $request): string|null
    {
        return 'product ' . $request->get('id');
    }
}
