<?php

namespace Studoo\EduFramework\Commands;

use Nette\PhpGenerator\PhpFile;
use Studoo\EduFramework\Commands\Exception\ControllerAlreadyExistsException;
use Studoo\EduFramework\Commands\Exception\RouteAlreadyExistsException;
use Studoo\EduFramework\Commands\Exception\ViewAlreadyExistsException;
use Studoo\EduFramework\Core\Controller\ControllerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

/**
 * Class CreateControllerCommand
 * Classe permettant l'utilisation de la commande edu:make:controller
 * Cette commande permet de générer un controller, sa vue associée
 * ainsi que déclarer la route dans le fichier de configuration
 * @package Studoo\EduFramework\Commands
 */
class CreateControllerCommand extends Command
{
    private const ROUTES_FILE_PATH = './app/Config/routes.yaml';
    private const TEMPLATES_DIR = './app/Template/';

    protected function configure(): void
    {
        $this->setDefinition([
            new InputArgument('controller-name', InputArgument::REQUIRED, 'Controller name'),
        ]);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws RouteAlreadyExistsException
     * @throws ControllerAlreadyExistsException
     * @throws ViewAlreadyExistsException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        //Format controller-name arg
        $namesCollection = self::getNamesCollection($input->getFirstArgument());
        //Generate route params in app/Config/routes.yaml
        self::generateRoute($namesCollection["uri"],$namesCollection["uri"],$namesCollection["className"]);
        //Generate Controller Class
        self::generateController($namesCollection["className"],$namesCollection["twigPath"]);
        //Generation TWIG
        self::generateView($namesCollection["twigDir"],$namesCollection["twigPath"]);
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

        $pieces = preg_split('/(?=[A-Z])/',$arg);
        array_shift($pieces);

        $className = ucfirst($arg)."Controller";
        $uri = "/".strtolower($arg);
        $twig = strtolower($arg);

        if(count($pieces)>1){
            $twig = implode("_",array_map(function($item){return strtolower($item);},$pieces));
            $uri = str_replace("_","-",$twig);
        }

        return [
            "uri"=>$uri,
            "twigDir"=>$twig,
            "twigPath"=>"$twig/$twig.html.twig",
            "className"=>$className
        ];
    }

    /**
     * Fonction permettant de générer la classe PHP
     * @param string $className
     * @param string $twigPath
     * @return void
     * @throws ControllerAlreadyExistsException
     */
    private function generateController(string $className, string $twigPath):void
    {
        $filename = "./app/Controller/$className.php";

        if(file_exists($filename)){
            throw new ControllerAlreadyExistsException();
        }

        $file = new PhpFile;
        //Add namespace Controller
        $namespace= $file->addNamespace("Controller");
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
            return TwigCore::getEnvironment()->render(?,
            [
                "titre"   => ?,
                "request" => $request
            ]
        );
        CODE,[$twigPath,$className]);

        //Create file
        file_put_contents($filename,$file);
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
        $router = Yaml::parseFile(self::ROUTES_FILE_PATH);

        if(array_key_exists($name,$router)){
            throw new RouteAlreadyExistsException();
        }

        $router[$name]=[
            "uri"=>"/".$uri,
            "controller"=>'Controller\\'.$className,
            "httpMethod"=>["GET"]
        ];

        file_put_contents(self::ROUTES_FILE_PATH,Yaml::dump($router));
    }

    /**
     * Fonction permettant de générer la vue TWIG
     * @param string $dir
     * @param string $path
     * @return void
     * @throws ViewAlreadyExistsException
     */
    private function generateView(string $dir, string $path): void
    {
        if(file_exists(self::TEMPLATES_DIR.$path)){
            throw new ViewAlreadyExistsException();
        }

        if(is_dir(self::TEMPLATES_DIR.$dir) === false){
            mkdir(self::TEMPLATES_DIR.$dir);
        }

        file_put_contents(self::TEMPLATES_DIR.$path,
            <<<'TWIG'
        {% extends "base.html.twig" %}

        {% block title %}{{ titre }}{% endblock %}

        {% block content %}
        <h1>{{ titre }}</h1>
        {% endblock %}
        TWIG
        );
    }
}
