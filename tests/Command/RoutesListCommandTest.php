<?php

namespace Command;

use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Commands\RoutesListCommand;
use Studoo\EduFramework\Core\ConfigCore;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests de la commande routes:list (liste des routes du fichier routes.yaml)
 */
class RoutesListCommandTest extends TestCase
{
    /**
     * Exécute la commande routes:list avec le dossier de configuration des routes indiqué
     * @param string $routeConfigPath Dossier contenant le fichier routes.yaml
     * @param array<string, mixed> $input Arguments et options de la commande
     * @return CommandTester
     */
    private function runCommand(string $routeConfigPath, array $input = []): CommandTester
    {
        (new ConfigCore(['route_config_path' => $routeConfigPath]));
        $application = new Application(ConfigCore::getConfig('name'), ConfigCore::getConfig('version'));
        $application->addCommand(new RoutesListCommand());
        $commandeTester = new CommandTester($application->find('routes:list'));
        $commandeTester->execute($input);

        return $commandeTester;
    }

    /**
     * Liste complète : une ligne par méthode HTTP, avec le nom de la route
     * @return void
     */
    public function testCommandRoutesList(): void
    {
        $commandeTester = $this->runCommand(__DIR__ . '/../Config/');
        $output = $commandeTester->getDisplay();

        $this->assertSame(Command::SUCCESS, $commandeTester->getStatusCode());
        $this->assertStringContainsString('Liste des routes', $output);
        // Une ligne par méthode HTTP, avec le nom de la route (clé du fichier routes.yaml)
        $this->assertMatchesRegularExpression('#GET\s+/home\s+Controller\\\\HomeController\s+home#', $output);
        $this->assertMatchesRegularExpression('#POST\s+/user/\{id\}\s+Controller\\\\UserController\s+user\s#', $output);
        // Le motif d'origine est conservé (segments optionnels inclus)
        $this->assertStringContainsString('/user/{id:\d+}[/{name}]', $output);
        $this->assertStringContainsString('Controller\MedecinController::index', $output);
        $this->assertStringContainsString('14 route(s)', $output);
    }

    /**
     * Option --method : seules les routes de la méthode HTTP sont affichées
     * @return void
     */
    public function testCommandRoutesListFilterByMethod(): void
    {
        $commandeTester = $this->runCommand(__DIR__ . '/../Config/', ['--method' => 'post']);
        $output = $commandeTester->getDisplay();

        $this->assertStringNotContainsString('/home', $output);
        $this->assertStringContainsString('/medecin/new', $output);
        $this->assertStringContainsString('5 route(s)', $output);
    }

    /**
     * Option --method sans route correspondante : message d'information
     * @return void
     */
    public function testCommandRoutesListFilterWithoutRoute(): void
    {
        $commandeTester = $this->runCommand(__DIR__ . '/../Config/', ['--method' => 'DELETE']);

        $this->assertSame(Command::SUCCESS, $commandeTester->getStatusCode());
        $this->assertStringContainsString('Aucune route pour la méthode HTTP <DELETE>', $commandeTester->getDisplay());
    }

    /**
     * Fichier routes.yaml absent : message d'erreur et code d'échec
     * @return void
     */
    public function testCommandRoutesListWithoutRouteFile(): void
    {
        $commandeTester = $this->runCommand(sys_get_temp_dir() . '/edu-framework-routes-inexistant-' . uniqid() . '/');

        $this->assertSame(Command::FAILURE, $commandeTester->getStatusCode());
        $this->assertStringContainsString("n'existe pas", $commandeTester->getDisplay());
    }

    /**
     * Route mal décrite dans routes.yaml : message d'erreur qui nomme la route
     * @return void
     */
    public function testCommandRoutesListWithInvalidRoute(): void
    {
        $commandeTester = $this->runCommand(__DIR__ . '/../Config/InvalidRoutes/');

        $this->assertSame(Command::FAILURE, $commandeTester->getStatusCode());
        $this->assertStringContainsString('sansController', $commandeTester->getDisplay());
    }
}
