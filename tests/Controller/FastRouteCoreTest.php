<?php

namespace Controller;

use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Controller\FastRouteCore;
use Studoo\EduFramework\Core\View\TwigCore;

class FastRouteCoreTest extends TestCase
{

    public function setUp(): void
    {
        (new ConfigCore([
            'twig_path' => __DIR__ . '/../../app/Template'
        ]));
        TwigCore::setEnvironment();
        $en = TwigCore::getEnvironment();
    }

    public function testGetDispatcher()
    {
        $route = new FastRouteCore();
        $route->addRoute('GET', '/', "Controller\HomeController");

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $this->assertEquals('df50808e9806dd3e0cceed154c35303d92a90ad6', sha1($route->getRoute()));
    }

    public function testGetDispatcherWithExceptionNotFound()
    {
        $route = new FastRouteCore();
        $route->addRoute('GET', '/', "Controller\HomeController");

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';
        $this->assertEquals('2280909eada820cab67a82fbdf5dfeba4024aa13', sha1($route->getRoute()));
    }

    public function testGetDispatcherWithExceptionMethodNotAllowed()
    {
        $route = new FastRouteCore();
        $route->addRoute('GET', '/', "Controller\HomeController");

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/';
        $this->assertEquals('ca8755da6c4454c8d769b2eeaedcb6ef689b583c', sha1($route->getRoute()));
    }

    public function testGetDispatcherWithExceptionMethodNotAllowedAndNotFound()
    {
        $route = new FastRouteCore();
        $route->addRoute('GET', '/', "Controller\HomeController");

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/test';
        $this->assertEquals('ca9cc12e0274e65bdda697a8b662f0713c5a3650', sha1($route->getRoute()));
    }

    public function testLoadRoutesMatchSuccess()
    {
        $route = new FastRouteCore();
        $route->loadRouteConfig(__DIR__ . '/../Config/');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $this->assertEquals('df50808e9806dd3e0cceed154c35303d92a90ad6', sha1($route->getRoute()));
    }

    public function testLoadRoutesMatchSuccessWithExceptionNotFound()
    {
        $route = new FastRouteCore();
        $route->loadRouteConfig(__DIR__ . '/../Config/');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';
        $this->assertEquals('2280909eada820cab67a82fbdf5dfeba4024aa13', sha1($route->getRoute()));
    }

    public function testLoadRoutesMatchSuccessWithExceptionMethodNotAllowedAndNotFound()
    {
        $route = new FastRouteCore();
        $route->loadRouteConfig(__DIR__ . '/../Config/');

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/';
        $this->assertEquals('ca8755da6c4454c8d769b2eeaedcb6ef689b583c', sha1($route->getRoute()));
    }
}
