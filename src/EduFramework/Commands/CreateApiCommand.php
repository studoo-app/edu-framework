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
        if(is_dir(self::CONTROLLER_DIR) === false) {
            mkdir(self::CONTROLLER_DIR);
        }
        if(is_dir(self::CONTROLLER_DIR . self::API_DIR) === false) {
            mkdir(self::CONTROLLER_DIR . self::API_DIR);
        }

        $io = new SymfonyStyle($input, $output);

        //Format controller-name arg
        $namesCollection = $this->getNamesCollection($input->getArgument('controller-name'));
        //Generate route params in app/Config/routes.yaml
        $this->generateRoute($namesCollection["uri"], $namesCollection["uri"], $namesCollection["className"]);
        //Generate Controller Class
        $this->generateController($namesCollection["className"]);
        //Close command message
        $io->success("Controller successfully generated");
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

        if(count($name) > 1) {
            $twig = implode("_", array_map(function ($item) {return strtolower($item);}, $name));
            $uri = str_replace("_", "-", $twig);
        }

        return [
            "uri" => $uri,
            "className" => $className
        ];
    }

    /**
     * Fonction permettant de générer la classe PHP
     * @param string $className Nom de la classe
     * @return void
     * @throws ControllerAlreadyExistsException
     */
    private function generateController(string $className): void
    {
        $filename = self::CONTROLLER_DIR . self::API_DIR .  "$className.php";

        if(file_exists($filename)) {
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
        //Generate ClassName
        $class = $namespace->addClass($className);
        //Add interface implementation
        $class->addImplement(ControllerInterface::class);
        //Create and design execute method
        $method = $class->addMethod('execute');
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
     * @param string $name
     * @param string $uri
     * @param string $className
     * @return void
     * @throws RouteAlreadyExistsException
     */
    private function generateRoute(string $name, string $uri, string $className): void
    {
        if(is_file(self::ROUTES_FILE_PATH) === false) {
            file_put_contents(self::ROUTES_FILE_PATH, '');
        }

        $router = Yaml::parseFile(self::ROUTES_FILE_PATH);

        if (!is_array($router)) {
            $router = [];
        }

        $indexName = "api_";
        if(array_key_exists($indexName . $name, $router)) {
            throw new RouteAlreadyExistsException();
        }

        $router[$indexName . $name] = [
            "uri" => "/" . self::API_DIR . $uri,
            "controller" => 'Controller\\api\\'.$className,
            "httpMethod" => ["GET"]
        ];

        file_put_contents(self::ROUTES_FILE_PATH, Yaml::dump($router));
    }
}
