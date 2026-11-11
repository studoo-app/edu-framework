<?php

namespace Controller;

use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Commands\RoutesListCommand;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Controller\FastRouteCore;
use Studoo\EduFramework\Core\Controller\Route;
use Studoo\EduFramework\Core\Exception\ErrorRouteConfigNotExistException;
use Studoo\EduFramework\Core\View\TwigCore;
use Studoo\EduRoute\BadRouteException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests des routes déclarées avec les attributs #[Route] et #[RouteGroup] (studoo/edu-route)
 * Controllers de test : tests/Fixtures/AttributeRoutes/
 */
class AttributeRouteTest extends TestCase
{
    private const string FIXTURES_PATH = __DIR__ . '/../Fixtures/';

    /**
     * Configuration : routes.yaml de test et controllers de test déclarés par attributs
     * @return void
     */
    public function setUp(): void
    {
        unset($_ENV['APP_ENV'], $_SERVER['APP_ENV']);
        $_POST = [];
        $_FILES = [];
        $this->configure(__DIR__ . '/../Config/', self::FIXTURES_PATH . 'AttributeRoutes/', 'AttributeRoutes\\');
    }

    /**
     * Configure les dossiers des routes et des controllers
     * @param string $routeConfigPath     Dossier du fichier routes.yaml
     * @param string $controllerPath      Dossier des controllers
     * @param string $controllerNamespace Namespace des controllers
     * @return void
     */
    private function configure(string $routeConfigPath, string $controllerPath, string $controllerNamespace): void
    {
        (new ConfigCore([
            'twig_path' => __DIR__ . '/../../app/Template',
            'route_config_path' => $routeConfigPath,
            'controller_path' => $controllerPath,
            'controller_namespace' => $controllerNamespace,
        ]));
        TwigCore::setEnvironment();
    }

    /**
     * Seuls les controllers qui déclarent une route sont gardés, dans l'ordre alphabétique des fichiers
     * @return void
     */
    public function testFindAttributeControllers(): void
    {
        $this->assertSame(
            ['AttributeRoutes\Admin\ProductController', 'AttributeRoutes\HelloController'],
            FastRouteCore::findAttributeControllers(self::FIXTURES_PATH . 'AttributeRoutes/', 'AttributeRoutes\\')
        );
    }

    /**
     * Dossier des controllers absent : aucun controller
     * @return void
     */
    public function testFindAttributeControllersWithoutDirectory(): void
    {
        $this->assertSame([], FastRouteCore::findAttributeControllers(self::FIXTURES_PATH . 'NotExist/', 'NotExist\\'));
    }

    /**
     * Dossier par défaut : app/Controller/ du projet (clé base_path)
     * @return void
     */
    public function testDefaultControllerPath(): void
    {
        (new ConfigCore(['base_path' => './']));
        $this->assertSame('./app/Controller/', FastRouteCore::getControllerPath());
        $this->assertSame('Controller\\', FastRouteCore::getControllerNamespace());
    }

    /**
     * Les routes du fichier routes.yaml sont déclarées en premier, puis celles des attributs
     * @return void
     */
    public function testLoadRoutesWithYamlAndAttributes(): void
    {
        $routes = (new FastRouteCore())->loadRoutes()->declaredRoutes();
        $names = array_values(array_unique(array_map(static fn ($route) => $route->name, $routes)));

        $this->assertSame('index', $names[0]);
        $this->assertSame(['admin_product', 'hello', 'hello_name'], array_slice($names, -3));
        $this->assertSame('/admin/product/{id:\d+}', $routes[count($routes) - 4]->pattern);
        $this->assertSame('AttributeRoutes\HelloController::show', end($routes)->handler);
    }

    /**
     * Sans fichier routes.yaml, les routes déclarées par attributs suffisent
     * @return void
     */
    public function testLoadRoutesWithAttributesOnly(): void
    {
        $this->configure(self::FIXTURES_PATH . 'NoRoutesYaml/', self::FIXTURES_PATH . 'AttributeRoutes/', 'AttributeRoutes\\');

        $this->assertCount(4, (new FastRouteCore())->loadRoutes()->declaredRoutes());
    }

    /**
     * Sans fichier routes.yaml ni attribut #[Route] : erreur explicite du fichier absent
     * @return void
     */
    public function testLoadRoutesWithoutAnyRoute(): void
    {
        $this->configure(self::FIXTURES_PATH . 'NoRoutesYaml/', self::FIXTURES_PATH . 'NotExist/', 'NotExist\\');

        $this->expectException(ErrorRouteConfigNotExistException::class);
        (new FastRouteCore())->loadRoutes();
    }

    /**
     * Une requête HTTP appelle la méthode du controller qui porte l'attribut #[Route]
     * @return void
     */
    public function testDispatchAttributeRoute(): void
    {
        $route = (new FastRouteCore())->loadRoutes();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/hello/benoit';
        $this->assertSame('hello benoit', $route->getRoute());

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/admin/product/42';
        $this->assertSame('product 42', $route->getRoute());
    }

    /**
     * getNameToPath() génère les URL des routes du fichier routes.yaml et des attributs #[Route]
     * @return void
     */
    public function testGetNameToPathWithAttributeRoutes(): void
    {
        $route = new Route();
        $this->assertSame('/home', $route->getNameToPath('home'));
        $this->assertSame('/hello', $route->getNameToPath('hello'));
        $this->assertSame('/hello/benoit', $route->getNameToPath('hello_name', ['name' => 'benoit']));
        $this->assertSame('/admin/product/42', $route->getNameToPath('admin_product', ['id' => 42]));
        $this->assertSame(['GET', 'POST'], $route->getRouteInfo()['hello_name']['httpMethod']);
    }

    /**
     * Un attribut #[Route] invalide (méthode statique) est signalé par le moteur de routage
     * @return void
     */
    public function testInvalidAttributeRoute(): void
    {
        $this->configure(
            __DIR__ . '/../Config/',
            self::FIXTURES_PATH . 'InvalidAttributeRoutes/',
            'InvalidAttributeRoutes\\'
        );

        $this->expectException(BadRouteException::class);
        (new FastRouteCore())->loadRoutes();
    }

    /**
     * La commande routes:list affiche aussi les routes déclarées par attributs
     * @return void
     */
    public function testRoutesListShowsAttributeRoutes(): void
    {
        $application = new Application();
        $application->addCommand(new RoutesListCommand());
        $commandeTester = new CommandTester($application->find('routes:list'));
        $commandeTester->execute([]);
        $output = $commandeTester->getDisplay();

        $this->assertMatchesRegularExpression('#GET\s+/admin/product/\{id:\\\\d\+\}\s+AttributeRoutes\\\\Admin\\\\ProductController::edit\s+admin_product#', $output);
        $this->assertStringContainsString('18 route(s)', $output);
    }
}
