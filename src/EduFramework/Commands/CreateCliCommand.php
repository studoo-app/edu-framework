<?php

namespace Studoo\EduFramework\Commands;

use Nette\PhpGenerator\PhpFile;
use Studoo\EduFramework\Commands\Exception\CommandAlreadyExistsException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

/**
 * Class CreateCliCommand
 * Classe permettant l'utilisation de la commande php bin/edu make:command <command-name>
 * Cette commande permet de générer une commande CLI dans le projet
 * ainsi que déclarer la commande dans le fichier de configuration
 * Example command line:
 * ```
 * $ php bin/edu make:command hello
 * ```
 * @package Studoo\EduFramework\Commands
 */
#[AsCommand(
    name: 'make:command',
    description: 'Génération command CLI',
)]
class CreateCliCommand extends Command
{
    private const COMMANDS_FILE_PATH = './app/Config/commands.yaml';

    private const COMMAND_DIR = './app/Command';

    protected function configure(): void
    {
        $this->AddArgument('command-name', InputArgument::REQUIRED, 'Commande name');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws CommandAlreadyExistsException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        //Format controller-name arg
        $namesCollection = $this->getNamesCollection($input->getArgument('command-name'));
        //Generate route params in app/Config/routes.yaml
        $this->generateCommandConfig($namesCollection["commandName"], $namesCollection["nameSpace"]);
        //Generate Command Class
        $this->generateCommand($namesCollection["className"], $namesCollection["commandName"]);

        //Close command message
        $io->success("Command successfully generated");
        return Command::SUCCESS;
    }

    /**
     * Fonction permettant le traitement de l'arg command-name passé à la commande
     * afin de générer les différents noms et chemins nécessaires
     * @param string $arg Nom de la commande
     * @return array
     */
    private function getNamesCollection(string $arg): array
    {
        $className = ucfirst($arg)."Command";
        $nameCommand = strtolower($arg);

        return [
            "commandName" => $nameCommand,
            "nameSpace" => "Command\\".$className,
            "className" => $className
        ];
    }

    /**
     * Fonction permettant de générer la classe PHP
     * @param string $className Nom de la classe
     * @param string $nameCommand Nom de la commande
     * @return void
     * @throws CommandAlreadyExistsException
     */
    private function generateCommand(string $className, string $nameCommand): void
    {
        if(is_dir(self::COMMAND_DIR) === false) {
            mkdir(self::COMMAND_DIR);
        }

        $filename = "./app/Command/$className.php";

        if(file_exists($filename)) {
            throw new CommandAlreadyExistsException();
        }

        $file = new PhpFile();
        //Add namespace Controller
        $namespace = $file->addNamespace("Command");
        //Add Imports
        $namespace->addUse('Studoo\EduFramework\Commands\Extends\CommandBanner');
        $namespace->addUse('Studoo\EduFramework\Core\ConfigCore');
        $namespace->addUse('Symfony\Component\Console\Command\Command');
        $namespace->addUse('Symfony\Component\Console\Input\InputInterface');
        $namespace->addUse('Symfony\Component\Console\Output\OutputInterface');
        //Generate ClassName
        $class = $namespace->addClass($className);
        $class->addAttribute('Symfony\Component\Console\Attribute\AsCommand', [
            'name' => strtolower($nameCommand),
            'description' => 'Renseigner la description de la commande ' . $nameCommand,
        ]);
        //Add extends CommandManage
        $class->setExtends('Studoo\EduFramework\Commands\Extends\CommandManage');
        //Create and design execute method
        $method = $class->addMethod('execute');
        $method->setReturnType('int');
        $method->addParameter('input')->setType('Symfony\Component\Console\Input\InputInterface');
        $method->addParameter('output')->setType('Symfony\Component\Console\Output\OutputInterface');
        $method->setBody(<<<'CODE'
             self::$stdOutput->writeln([
                CommandBanner::getBanner(),
                'Bienvenue dans la console ' . ConfigCore::getConfig('name'),
                '',
            ]);
            // Ajouter votre code ici
            return Command::SUCCESS;
        CODE);

        //Create file
        file_put_contents($filename, $file);
    }

    /**
     * Fonction permettant d'ajouter la route au fichier de configuration
     * Par défaut la méthode lors de la génération est GET
     * @param string $name Command name
     * @param string $nameSpace Command namespace
     * @return void
     * @throws CommandAlreadyExistsException
     */
    private function generateCommandConfig(string $name, string $nameSpace): void
    {
        if(is_file(self::COMMANDS_FILE_PATH) === false) {
            file_put_contents(self::COMMANDS_FILE_PATH, '');
        }

        $commands = Yaml::parseFile(self::COMMANDS_FILE_PATH);

        if (!is_array($commands)) {
            $commands = [];
        }

        if (array_key_exists($name, $commands)) {
            throw new CommandAlreadyExistsException();
        }

        $commands[$name] = $nameSpace;

        file_put_contents(self::COMMANDS_FILE_PATH, Yaml::dump($commands));
    }
}
