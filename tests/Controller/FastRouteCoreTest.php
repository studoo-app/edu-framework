<?php

namespace Controller;

use FastRoute\RouteCollector;
use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Core\Controller\FastRouteCore;
use Studoo\EduFramework\Core\View\TwigCore;

class FastRouteCoreTest extends TestCase
{

    public function setUp(): void
    {
        (new TwigCore(__DIR__ . '/../../app/Template'));
        $en = TwigCore::getEnvironment();
    }

    public function testGetDispatcher()
    {
        $dispatcher = \FastRoute\simpleDispatcher(function (\FastRoute\RouteCollector $route) {
            $route->addRoute('GET', '/', "Controller\HomeController");
        });
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $this->assertEquals('d83d4bc21c33c6860bae0fe31f3aed41ba0d4038', sha1((new FastRouteCore)->getDispatcher($dispatcher)));
    }

    public function testGetDispatcherWithExceptionNotFound()
    {
        $dispatcher = \FastRoute\simpleDispatcher(function (\FastRoute\RouteCollector $route) {
            $route->addRoute('GET', '/', "Controller\HomeController");
        });
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';
        $this->assertEquals('b9f6849141244d2723a15be3c419c3d47262b8c5', sha1((new FastRouteCore)->getDispatcher($dispatcher)));
    }

    public function testGetDispatcherWithExceptionMethodNotAllowed()
    {
        $dispatcher = \FastRoute\simpleDispatcher(function (\FastRoute\RouteCollector $route) {
            $route->addRoute('GET', '/', "Controller\HomeController");
        });
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/';
        $this->assertEquals('73a92167819882a2fc0c64b0a16a0b9f8c28b864', sha1((new FastRouteCore)->getDispatcher($dispatcher)));
    }

    public function testGetDispatcherWithExceptionMethodNotAllowedAndNotFound()
    {
        $dispatcher = \FastRoute\simpleDispatcher(function (\FastRoute\RouteCollector $route) {
            $route->addRoute('GET', '/', "Controller\HomeController");
        });
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/test';
        $this->assertEquals('b9f6849141244d2723a15be3c419c3d47262b8c5', sha1((new FastRouteCore)->getDispatcher($dispatcher)));
    }
}
