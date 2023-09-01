<?php

namespace Studoo\EduFramework\Commands;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Studoo\EduFramework\Core\Controller\ControllerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

class CreateControllerCommand extends Command
{
    protected function configure(): void
    {
        $this->setDefinition([
            new InputArgument('controller-name', InputArgument::REQUIRED, 'Controller name'),
        ]);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {

        //Format controller-name arg
        //TODO NORMALIZE FILENAME, REMOVE - OR SPLIT ON CAPS
        //$pieces = preg_split('/(?=[A-Z])/',$str);
        $baseFilename = ucfirst($input->getFirstArgument());
        $controllerFileName = $baseFilename."Controller";

        //Generate file
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
        $class = $namespace->addClass($controllerFileName);
        //Add interface implementation
        $class->addImplement(ControllerInterface::class);
        //Create and design execute method
        $method = $class->addMethod('execute');
        $method->setReturnType('string|null');
        $method->addParameter('request')->setType('Studoo\EduFramework\Core\Controller\Request');
        $method->setBody(<<<'CODE'
            return TwigCore::getEnvironment()->render(?,
            [
                "titre"   => "Hello World !",
                "request" => $request
            ]
        );
        CODE,["test/test.html.twig"]); //TODO generate directory and file names for twig

        //Create file in app/Controller controller directory
        file_put_contents("./app/Controller/$controllerFileName.php",$file);


        //Generate route params in app/Config/routes.yaml
        $router = Yaml::parseFile('./app/Config/routes.yaml');


        $route = [
            "uri"=>"/".$input->getFirstArgument(),
            "controller"=>'Controller\\'.$controllerFileName,
            "httpMethod"=>["GET"]
        ];

        $router[$input->getFirstArgument()]=$route;

        file_put_contents('./app/Config/routes.yaml',Yaml::dump($router));

        //Generate TWIG

        if(is_dir("./app/Template/".$input->getFirstArgument()) === false){
            mkdir("./app/Template/".$input->getFirstArgument());
        }

        file_put_contents('./app/Template/'.$input->getFirstArgument().'/'.$input->getFirstArgument().'.html.twig',
        <<<'TWIG'
        {% extends "base.html.twig" %}

        {% block title %}{{ titre }}{% endblock %}

        {% block content %}
        <h1>{{ titre }}</h1>
        {% endblock %}
        TWIG
        );

        //Close command message
        $io = new SymfonyStyle($input, $output);
        $io->success("Controller successfully generated");
        return 0;
    }


}
