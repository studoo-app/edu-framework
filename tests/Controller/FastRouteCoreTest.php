<?php

namespace Controller;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Controller\FastRouteCore;
use Studoo\EduFramework\Core\View\TwigCore;

class FastRouteCoreTest extends TestCase
{

    public function setUp(): void
    {
        (new ConfigCore([
            'twig_path' => __DIR__ . '/../../app/Template',
            'route_config_path' => __DIR__ . "/../Config/"
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
        $this->assertEquals('d7045e6af8910d38af6c42f0b610e51c644e5ec1', sha1($route->getRoute()));
    }

    public function testGetDispatcherWithExceptionNotFound()
    {
        $route = new FastRouteCore();
        $route->addRoute('GET', '/', "Controller\HomeController");

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';
        $this->assertEquals('1ab50275de77fa6215e882b0833350c32cfcd7a2', sha1($route->getRoute()));
    }

    public function testGetDispatcherWithExceptionMethodNotAllowed()
    {
        $route = new FastRouteCore();
        $route->addRoute('GET', '/', "Controller\HomeController");

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/';
        $this->assertEquals('ed2ad24f02864b53319ab611d414ceac5c322751', sha1($route->getRoute()));
    }

    public function testGetDispatcherWithExceptionMethodNotAllowedAndNotFound()
    {
        $route = new FastRouteCore();
        $route->addRoute('GET', '/', "Controller\HomeController");

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/test';
        $this->assertEquals('1ab50275de77fa6215e882b0833350c32cfcd7a2', sha1($route->getRoute()));
    }

    public function testLoadRoutesMatchSuccess()
    {
        $route = new FastRouteCore();
        $route->loadRouteConfig(__DIR__ . '/../Config/');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $this->assertEquals('d7045e6af8910d38af6c42f0b610e51c644e5ec1', sha1($route->getRoute()));
    }

    public function testLoadRoutesMatchSuccessWithExceptionNotFound()
    {
        $route = new FastRouteCore();
        $route->loadRouteConfig(__DIR__ . '/../Config/');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';
        $this->assertEquals('1ab50275de77fa6215e882b0833350c32cfcd7a2', sha1($route->getRoute()));
    }

    public function testLoadRoutesMatchSuccessWithExceptionMethodNotAllowedAndNotFound()
    {
        $route = new FastRouteCore();
        $route->loadRouteConfig(__DIR__ . '/../Config/');

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/';
        $this->assertEquals('ed2ad24f02864b53319ab611d414ceac5c322751', sha1($route->getRoute()));
    }
}
