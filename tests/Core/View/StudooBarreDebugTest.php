<?php

namespace Core\View;

use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Controller\Request;
use Studoo\EduFramework\Core\View\studooBarreDebug;

class StudooBarreDebugTest extends TestCase
{
    protected function setUp(): void
    {
        new ConfigCore([]);
    }

    public function testToolbarDisplaysMethodRouteAndController(): void
    {
        ConfigCore::setRequest((new Request('/users/12', 'GET'))->setHander('App\\Controller\\UserController'));

        $html = (new studooBarreDebug())->generateBarDebug();

        $this->assertStringContainsString('id="edu-tb"', $html);
        $this->assertStringContainsString('/users/12', $html);
        $this->assertStringContainsString('App\\Controller\\UserController', $html);
        $this->assertStringContainsString('PHP <b>' . PHP_VERSION . '</b>', $html);
    }

    public function testToolbarEscapesTheRoute(): void
    {
        ConfigCore::setRequest(new Request('/<script>alert(1)</script>', 'GET'));

        $html = (new studooBarreDebug())->generateBarDebug();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function testToolbarWithoutControllerDoesNotFail(): void
    {
        ConfigCore::setRequest(new Request('/', 'GET'));

        $this->assertStringContainsString('aucun', (new studooBarreDebug())->generateBarDebug());
    }
}
