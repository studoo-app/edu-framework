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

use Studoo\EduFramework\Commands\Extends\CommandBanner;
use Studoo\EduFramework\Commands\Extends\CommandManage;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Controller\FastRouteCore;
use Studoo\EduFramework\Core\Exception\ErrorConfigException;
use Studoo\EduFramework\Core\Exception\ErrorRouteConfigNotExistException;
use Studoo\EduRoute\BadRouteException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Class RoutesListCommand
 * Classe permettant de lister les routes de l'application (fichier app/Config/routes.yaml)
 * Example command line:
 * ```
 * $ php bin/edu routes:list
 * $ php bin/edu routes:list --method=POST
 * ```
 * @package Studoo\EduFramework\Commands
 */
#[AsCommand(
    name: 'routes:list',
    description: 'Liste les routes de l\'application (fichier app/Config/routes.yaml)',
)]
class RoutesListCommand extends CommandManage
{
    protected function configure(): void
    {
        $this->addOption(
            'method',
            'm',
            InputOption::VALUE_REQUIRED,
            'Affiche uniquement les routes d\'une méthode HTTP (Exemple: GET, POST)'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        self::$stdOutput->writeln([
            CommandBanner::getBanner(),
            'Liste des routes de l\'application',
            ''
        ]);

        // Chargement des routes depuis le fichier de configuration (clé route_config_path)
        // avec les mêmes contrôles que lors d'une requête HTTP
        $routeCore = new FastRouteCore();
        try {
            $routeCore->loadRouteConfig(ConfigCore::getConfig('route_config_path'));
        } catch (ErrorRouteConfigNotExistException | ErrorConfigException | BadRouteException | ParseException $exception) {
            self::$stdOutput->error($exception->getMessage());
            return Command::FAILURE;
        }

        // Une ligne par méthode HTTP, dans l'ordre du fichier routes.yaml
        $method = $input->getOption('method');
        $rows = [];
        foreach ($routeCore->declaredRoutes() as $route) {
            $routeMethod = $route->isFallback() ? 'ANY' : $route->method;
            if (is_string($method) && strcasecmp($routeMethod, $method) !== 0) {
                continue;
            }
            $rows[] = [$routeMethod, $route->pattern, $route->describeHandler(), $route->name ?? '-'];
        }

        if ($rows === []) {
            self::$stdOutput->info(
                is_string($method)
                    ? "Aucune route pour la méthode HTTP <$method>"
                    : "Aucune route déclarée dans le fichier routes.yaml"
            );
            return Command::SUCCESS;
        }

        self::$stdOutput->table(['Méthode', 'URI', 'Contrôleur', 'Nom'], $rows);
        self::$stdOutput->writeln(count($rows) . ' route(s)');

        return Command::SUCCESS;
    }
}
