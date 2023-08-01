<?php

namespace Studoo\EduFramework\Core\Controller;

use FastRoute\Dispatcher;
use Studoo\EduFramework\Core\Controller\Error\HttpController;

class FastRouteCore
{
    public static function getDispatcher($dispatcher)
    {

        // Fetch method and URI from somewhere
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];

        // Strip query string (?foo=bar) and decode URI
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        $request = new Request($uri, $httpMethod);

        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                // ... 404 Not Found
                $httpController = new HttpController();
                return $httpController->execute($request);
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                // TODO mettre les erreurs
                // ... 405 Method Not Allowed
                break;
            case Dispatcher::FOUND:
                $request->setHander($routeInfo[1])->setVars($routeInfo[2]);
                $handler = $request->getHander();
                $exeController = new $handler(); // -> Creation du controller correspondant à la demande
                return $exeController->execute($request);
        }
    }
}