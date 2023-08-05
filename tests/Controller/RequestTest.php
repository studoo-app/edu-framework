<?php

namespace Controller;

use Studoo\EduFramework\Core\Controller\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testSetHander()
    {
        $request = new Request("/test", "GET");
        $request->setHander("test");
        $this->assertEquals("test", $request->getHander());
    }

    public function testGetHttpMethod()
    {
        $request = new Request("/test", "GET");
        $this->assertEquals("GET", $request->getHttpMethod());
    }

    public function testSetHttpMethod()
    {
        $request = new Request("/test", "GET");
        $request->setHttpMethod("POST");
        $this->assertEquals("POST", $request->getHttpMethod());
    }

    public function testGetVars()
    {
        $request = new Request("/test", "GET");
        $this->assertEquals([], $request->getVars());
    }

    public function testGetRoute()
    {
        $request = new Request("/test", "GET");
        $this->assertEquals("/test", $request->getRoute());
    }

    public function testSetRoute()
    {
        $request = new Request("/test", "GET");
        $request->setRoute("/test2");
        $this->assertEquals("/test2", $request->getRoute());
    }

    public function testSetVars()
    {
        $request = new Request("/test", "GET");
        $request->setVars(["test" => "test"]);
        $this->assertEquals(["test" => "test"], $request->getVars());
    }

    public function testGet()
    {
        $request = new Request("/test", "GET");
        $request->setVars(["testVars" => "test"]);
        $this->assertEquals("test", $request->get("testVars"));
    }

    public function testGetHander()
    {
        $request = new Request("/test", "GET");
        $request->setHander("Controller\HomeController");
        $this->assertEquals("Controller\HomeController", $request->getHander());
    }
}
