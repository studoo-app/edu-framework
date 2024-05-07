<?php

namespace Controller;

use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Controller\Route;
use Studoo\EduFramework\Core\View\TwigCore;

class RouteTest extends TestCase
{

    public function setUp(): void
    {
        (new ConfigCore([
            'twig_path' => __DIR__ . '/../../app/Template'
        ]));
        TwigCore::setEnvironment();
        $en = TwigCore::getEnvironment();
    }

    public function testGetListRoute()
    {
        $route = new Route();
        $this->assertEquals('/home', $route->getRouteInfo()['home']['uri']);
    }

    public function testGetListRouteParam()
    {
        $route = new Route();
        $this->assertEquals('/user/{id}', $route->getRouteInfo(__DIR__ . "/../Config/")['user']['uri']);
    }
}