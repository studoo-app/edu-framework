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
        // Neutralise APP_ENV : un test précédent (AppCommandTest) charge le .env
        // racine (APP_ENV=dev) de maniere immutable, ce qui declencherait l'injection
        // de la barre de debug dans le rendu et casserait les hashs ci-dessous.
        unset($_ENV['APP_ENV'], $_SERVER['APP_ENV']);

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
        $this->assertEquals('a2bd992a139c4ec5c9a3cac42e646dfc4a8e026c719d37924f193e7ff06fe520', hash('sha256', $route->getRoute()));
    }

    public function testGetDispatcherWithExceptionNotFound()
    {
        $route = new FastRouteCore();
        $route->addRoute('GET', '/', "Controller\HomeController");

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';
        $this->assertEquals('87564c80a8cc960d03fbca933aca2ad64d7db9ad1e61add6b1eb73941ea56a59', hash('sha256', $route->getRoute()));
    }

    public function testGetDispatcherWithExceptionMethodNotAllowed()
    {
        $route = new FastRouteCore();
        $route->addRoute('GET', '/', "Controller\HomeController");

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/';
        $this->assertEquals('a9acd5c197406f715176da90614cc9998d47683d2c9b0235d472aef972b36089', hash('sha256', $route->getRoute()));
    }

    public function testGetDispatcherWithExceptionMethodNotAllowedAndNotFound()
    {
        $route = new FastRouteCore();
        $route->addRoute('GET', '/', "Controller\HomeController");

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/test';
        $this->assertEquals('87564c80a8cc960d03fbca933aca2ad64d7db9ad1e61add6b1eb73941ea56a59', hash('sha256', $route->getRoute()));
    }

    public function testLoadRoutesMatchSuccess()
    {
        $route = new FastRouteCore();
        $route->loadRouteConfig(__DIR__ . '/../Config/');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $this->assertEquals('a2bd992a139c4ec5c9a3cac42e646dfc4a8e026c719d37924f193e7ff06fe520', hash('sha256', $route->getRoute()));
    }

    public function testLoadRoutesMatchSuccessWithExceptionNotFound()
    {
        $route = new FastRouteCore();
        $route->loadRouteConfig(__DIR__ . '/../Config/');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';
        $this->assertEquals('87564c80a8cc960d03fbca933aca2ad64d7db9ad1e61add6b1eb73941ea56a59', hash('sha256', $route->getRoute()));
    }

    public function testLoadRoutesMatchSuccessWithExceptionMethodNotAllowedAndNotFound()
    {
        $route = new FastRouteCore();
        $route->loadRouteConfig(__DIR__ . '/../Config/');

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/';
        $this->assertEquals('a9acd5c197406f715176da90614cc9998d47683d2c9b0235d472aef972b36089', hash('sha256', $route->getRoute()));
    }

    public function testLinkToGetnametopathInTwig()
    {
        $route = new FastRouteCore();
        $route->loadRouteConfig(__DIR__ . '/../Config/');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/link';

        $this->assertEquals('703d619fc8bdbb20450b15dea56caf9b193f6f84d8a8b5187ec9fc36ba4c63a8', hash('sha256', $route->getRoute()));
    }

    public function testLinkToGetnametopathInTwigUserid()
    {
        $route = new FastRouteCore();
        $route->loadRouteConfig(__DIR__ . '/../Config/');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/user/21';

        $this->assertEquals('ab2550a7609a2997e9aaef4b49a12388d8978d5507d970cb45fa96fec99e768b', hash('sha256', $route->getRoute()));
    }
}
