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
       // var_dump($input->getFirstArgument());

        //Format controller-name arg
        $baseFilename = ucfirst($input->getFirstArgument());
        $controllerFileName = $baseFilename."Controller";
        //Generate Controller Class

        //Generate file
        $file = new PhpFile;
        $namespace= $file->addNamespace("Controller");
        $namespace->addUse('Studoo\EduFramework\Core\Controller\ControllerInterface');
        $namespace->addUse('Studoo\EduFramework\Core\Controller\Request');
        $namespace->addUse('Studoo\EduFramework\Core\View\TwigCore');
        $namespace->addUse('Twig\Error\LoaderError');
        $namespace->addUse('Twig\Error\RuntimeError');
        $namespace->addUse('Twig\Error\SyntaxError');
        $class = $namespace->addClass($controllerFileName);
        $class->addImplement(ControllerInterface::class);
        $method = $class->addMethod('execute');
        $method->setReturnType('string|null');
        $method->addParameter('request')->setType('Request');
        $method->addBody('return TwigCore::getEnvironment()->render("home/home.html.twig",');
        $method->addBody('["titre" => "Hello World !", "requete" => $request]');
        $method->addBody(');');


        echo $file;

        //Generate route params in app/Config/routes.yaml
        //Yaml::parseFile('./app/Config/routes.yaml');

        file_put_contents("./app/Controller/$controllerFileName.php",$file);

        return 0;
    }
}
