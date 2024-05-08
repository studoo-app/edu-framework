<?php

namespace Controller;

use Studoo\EduFramework\Core\Exception\BadRouteException;
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
        $this->assertEquals('/', $route->getRouteInfo(__DIR__ . "/../Config/")['index']['uri']);
    }

    public function testGetListRouteParam()
    {
        $route = new Route();
        $this->assertEquals('/user/{id}', $route->getRouteInfo(__DIR__ . "/../Config/")['user']['uri']);
    }

    public function testGetListRouteParamForSlash()
    {
        $route = new Route();
        $this->assertEquals('/user/{id}/update', $route->getRouteInfo(__DIR__ . "/../Config/")['userUpdate']['uri']);
    }

    public function testGetListRouteParamForConsideredOptional()
    {
        $route = new Route();
        $this->assertEquals('/user/{id:\d+}[/{name}]', $route->getRouteInfo(__DIR__ . "/../Config/")['userName']['uri']);
    }

    public function testGetListRouteParamForConsideredOptionalAndSlash()
    {
        $route = new Route();
        $dd = $route->getRouteInfo(__DIR__ . "/../Config/");
        $this->assertEquals('/user/1/benoit', $route->getNameToPath('userName', ['id' => 1, 'name' => 'benoit']));
    }

    public function testGetListRouteParamForConsideredOptionalEnd()
    {
        $route = new Route();
        $dd = $route->getRouteInfo(__DIR__ . "/../Config/");
        $this->assertEquals('/user/1', $route->getNameToPath('userName', ['id' => 1]));
    }

    public function testGetListRouteParamForException()
    {
        $route = new Route();
        $dd = $route->getRouteInfo(__DIR__ . "/../Config/");
        $this->expectException(BadRouteException::class);
        $this->assertEquals('/user/1', $route->getNameToPath('userName', ['name' => 'benoit']));
    }

    public function testGetListRouteNameForException()
    {
        $route = new Route();
        $dd = $route->getRouteInfo(__DIR__ . "/../Config/");
        $this->expectException(BadRouteException::class);
        $this->assertEquals('/user/1', $route->getNameToPath('userNameError', ['id' => 2]));
    }
}