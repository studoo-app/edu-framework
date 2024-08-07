<?php
/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Commands;

use Nette\PhpGenerator\PhpFile;
use PhpParser\Node\Expr\Array_;
use Studoo\EduFramework\Commands\Exception\ControllerAlreadyExistsException;
use Studoo\EduFramework\Commands\Exception\RouteAlreadyExistsException;
use Studoo\EduFramework\Core\Controller\ControllerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

/**
 * CreateApiCommand class
 * Classe permettant l'utilisation de la commande php bin/edu make:api <controller-name>
 * Cette commande permet de générer un controller api, sa vue associée au format JSON
 * Example command line:
 * ```
 * $ php bin/edu make:api hello
 * ```
 * @package Studoo\EduFramework\Commands
 */
#[AsCommand(
    name: 'make:api',
    description: 'Génération controller api',
)]
class CreateApiCommand extends Command
{
    private const ROUTES_FILE_PATH = './app/Config/routes.yaml';
    private const CONTROLLER_DIR = './app/Controller/';
    private const API_DIR = 'api/';

    protected function configure(): void
    {
        $this->AddArgument('controller-name', InputArgument::REQUIRED, 'Controller name');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws RouteAlreadyExistsException
     * @throws ControllerAlreadyExistsException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        // Creation du dossier Controller/api
        if (is_dir(self::CONTROLLER_DIR) === false) {
            mkdir(self::CONTROLLER_DIR);
        }
        if (is_dir(self::CONTROLLER_DIR . self::API_DIR) === false) {
            mkdir(self::CONTROLLER_DIR . self::API_DIR);
        }

        $io = new SymfonyStyle($input, $output);

        //Format controller-name arg
        $namesCollection = $this->getNamesCollection($input->getArgument('controller-name'));
        //Generate route params in app/Config/routes.yaml
        $router = $this->generateRoute($namesCollection["uri"], $namesCollection["uri"], $namesCollection["className"]);
        // Generate openapi.php
        $this->generateOpenApi();
        //Generate Controller Class
        $this->generateController($namesCollection["className"], $router);
        //Close command message
        $io->block(
            [
                "Class : " . self::CONTROLLER_DIR . self::API_DIR . $namesCollection["className"],
                "URI : " . $router['uri']
            ]
        );
        $io->success("Controller and route successfully generated");
        return Command::SUCCESS;
    }

    /**
     * Fonction permettant le traitement de l'arg controller-name passé à la commande
     * afin de générer les différents noms et chemins nécéssaires
     * @param string $arg
     * @return array
     */
    private function getNamesCollection(string $arg): array
    {

        $name = preg_split('/(?=[A-Z])/', $arg);
        array_shift($name);

        $className = ucfirst($arg)."Controller";
        $uri = strtolower($arg);

        if (count($name) > 1) {
            $twig = implode("_", array_map(function ($item) {return strtolower($item);}, $name));
            $uri = str_replace("_", "-", $twig);
        }

        return [
            "uri"       => $uri,
            "className" => $className
        ];
    }

    /**
     * Fonction permettant de générer la classe PHP
     * @param string $className Nom de la classe
     * @param array $router Route générée
     * @return void
     * @throws ControllerAlreadyExistsException
     */
    private function generateController(string $className, array $router): void
    {
        $filename = self::CONTROLLER_DIR . self::API_DIR .  "$className.php";

        if (file_exists($filename) === true) {
            throw new ControllerAlreadyExistsException();
        }

        $file = new PhpFile();
        //Add namespace Controller
        $namespace = $file->addNamespace("Controller\api");
        //Add Imports
        $namespace->addUse('Studoo\EduFramework\Core\Controller\ControllerInterface');
        $namespace->addUse('Studoo\EduFramework\Core\Controller\Request');
        $namespace->addUse('Studoo\EduFramework\Core\View\TwigCore');
        $namespace->addUse('Twig\Error\LoaderError');
        $namespace->addUse('Twig\Error\RuntimeError');
        $namespace->addUse('Twig\Error\SyntaxError');
        $namespace->addUse('OpenApi\Attributes');
        //Generate ClassName
        $class = $namespace->addClass($className);
        //Add interface implementation
        $class->addImplement(ControllerInterface::class);
        //Create and design execute method
        $method = $class->addMethod('execute');
        $method->addAttribute('OpenApi\\Attributes\\Get', ['path' => $router['uri']]);
        $method->addAttribute('OpenApi\\Attributes\\Response', ['response' => '200', 'description' => 'Mettre une description']);
        $method->setReturnType('string|null');
        $method->addParameter('request')->setType('Studoo\EduFramework\Core\Controller\Request');
        $method->setBody(<<<'CODE'
        header('Content-Type: application/json');
        
        $listTest = [
            0 => ["nom" => "Yohaio", "prenom" => "Benoit"],
            1 => ["nom" => "Toma", "prenom" => "Yann"]
        ];

        return json_encode($listTest);
        CODE);

        //Create file
        file_put_contents($filename, $file);
    }

    /**
     * Fonction permettant d'ajouter la route au fichier de configuration
     * Par défaut la méthode lors de la génération est GET
     * @param string $name Nom de la route
     * @param string $uri URI de la route
     * @param string $className Nom de la classe
     * @return array $router Route générée
     * @throws RouteAlreadyExistsException
     */
    private function generateRoute(string $name, string $uri, string $className): array
    {
        if (is_file(self::ROUTES_FILE_PATH) === false) {
            file_put_contents(self::ROUTES_FILE_PATH, '');
        }

        $router = Yaml::parseFile(self::ROUTES_FILE_PATH);

        if (is_array($router) === false) {
            $router = [];
        }

        $indexName = "api_";
        if (array_key_exists($indexName . $name, $router) === true) {
            throw new RouteAlreadyExistsException();
        }

        $router[$indexName . $name] = [
            "uri" => "/" . self::API_DIR . $uri,
            "controller" => 'Controller\\api\\'.$className,
            "httpMethod" => ["GET"]
        ];

        file_put_contents(self::ROUTES_FILE_PATH, Yaml::dump($router));

        return $router[$indexName . $name];
    }

    private function generateOpenApi(): void
    {
        $filename = self::CONTROLLER_DIR . self::API_DIR . 'openapi.php';
        if (file_exists($filename) === true) {
            return;
        }

        $file = new PhpFile();
        $namespace = $file->addNamespace("Controller\api");
        $namespace->addUse('OpenApi\Attributes');
        $class = $namespace->addClass('openapi');
        $class->addAttribute('OpenApi\\Attributes\\Info', ['title' => 'My First API', 'version' => '0.1']);
        file_put_contents($filename, $file);
    }
}
