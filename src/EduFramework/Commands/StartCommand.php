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

use Studoo\EduFramework\Commands\Extends\CkeckStack;
use Studoo\EduFramework\Commands\Extends\CommandBanner;
use Studoo\EduFramework\Commands\Extends\CommandManage;
use Studoo\EduFramework\Core\ConfigCore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class StartCommand
 * Classe démarrer le serveur de développement
 * @package Studoo\EduFramework\Commands
 * Example command line:
 * ```
 * $ php bin/edu start
 * ```
 */
#[AsCommand(
    name: 'start',
    description: 'Démarrer le serveur de développement',
)]
class StartCommand extends CommandManage
{
    /**
     * Configure la commande en ajoutant les options nécessaires.
     *
     * Cette méthode est appelée pour configurer la commande avant son exécution.
     * Elle permet d'ajouter des options à la commande, comme le port d'écoute
     * du serveur de développement. L'option 'port' peut être spécifiée en
     * utilisant `-p` ou `--port` dans la ligne de commande. Si aucune valeur
     * n'est fournie, la valeur par défaut de 8042 sera utilisée.
     *
     * @return void
     */
    protected function configure(): void
    {
        parent::configure();

        $this->addOption(
            'port',
            'p',
            InputOption::VALUE_OPTIONAL,
            "Port d\'écoute du serveur de développement",
            8042
        );
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Récupération du port en option
        $port = $input->getOption('port');

        self::$stdOutput->writeln([
            CommandBanner::getBanner(),
            'Check des prérequis d\'' . ConfigCore::getConfig('name'),
            ''
        ]);

        $check = new CkeckStack($output, self::$stdOutput);
        $check->render();

        //self::$stdOutput->info();

        self::$stdOutput->writeln([
            '',
            CommandBanner::getDoc(),
            'Démarage du serveur de développement...',
            ''
        ]);

        self::$stdOutput->note(
            'Pour arrêter le serveur de développement, appuyer sur CTRL+X CTRL+C'
        );

//        exec("Composer\\Config::disableProcessTimeout",);
        exec("php -S localhost:$port -t public");

        return Command::SUCCESS;
    }
}
