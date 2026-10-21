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

use Studoo\EduFramework\Core\ConfigCore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class CreateDeployCommand
 * Classe permettant l'utilisation de la commande php bin/edu make:deploy
 * Cette commande génère le fichier frankendeploy.yaml qui décrit le déploiement
 * de l'application sur un serveur (VPS Debian ou Ubuntu) avec FrankenDeploy
 * https://yoanbernabeu.github.io/frankendeploy/
 * Example command line:
 * ```
 * $ php bin/edu make:deploy --domain=mon-app.fr
 * $ php bin/edu make:deploy --name=mon-app --domain=mon-app.fr --database=sqlite
 * ```
 * @package Studoo\EduFramework\Commands
 */
#[AsCommand(
    name: 'make:deploy',
    description: 'Génération de la configuration de déploiement (FrankenDeploy)',
)]
class CreateDeployCommand extends Command
{
    private const CONFIG_FILE = 'frankendeploy.yaml';

    /**
     * Bases de données proposées : driver FrankenDeploy => version de l'image
     */
    private const DATABASES = [
        'mariadb' => '11',
        'mysql' => '8.4',
        'sqlite' => null,
        'none' => null,
    ];

    /**
     * Fichiers nécessaires à la construction de l'image de production
     */
    private const REQUIRED_FILES = ['Dockerfile', 'docker-entrypoint.sh', 'Caddyfile', '.dockerignore'];

    /**
     * Déclare les options de la commande : --name, --domain, --database et --force
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->addOption('name', null, InputOption::VALUE_REQUIRED, "Nom de l'application sur le serveur")
            ->addOption('domain', null, InputOption::VALUE_REQUIRED, "Nom de domaine de l'application")
            ->addOption(
                'database',
                null,
                InputOption::VALUE_REQUIRED,
                'Base de données : ' . implode(', ', array_keys(self::DATABASES)),
                'mariadb'
            )
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Écrase le fichier frankendeploy.yaml existant');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $console = new SymfonyStyle($input, $output);
        $basePath = (string) ConfigCore::getConfig('base_path');

        $name = $this->resolveName($input->getOption('name'), $basePath);
        if ($name === '') {
            $console->error("Nom d'application invalide : utilisez des minuscules, des chiffres et des tirets. "
                . "Dans le conteneur Docker (dossier /app), précisez le nom : --name=mon-app");
            return Command::FAILURE;
        }

        $database = (string) $input->getOption('database');
        if (array_key_exists($database, self::DATABASES) === false) {
            $console->error('Base de données inconnue : ' . $database
                . ' (valeurs possibles : ' . implode(', ', array_keys(self::DATABASES)) . ')');
            return Command::FAILURE;
        }

        $domain = $input->getOption('domain');
        if ($domain !== null && preg_match('/^(?!-)[a-z0-9.-]+\.[a-z]{2,}$/', strtolower($domain)) !== 1) {
            $console->error('Nom de domaine invalide : ' . $domain);
            return Command::FAILURE;
        }

        $configPath = $basePath . self::CONFIG_FILE;
        if (file_exists($configPath) === true && $input->getOption('force') === false) {
            $console->error('Le fichier ' . self::CONFIG_FILE . ' existe déjà (utilisez --force pour l\'écraser)');
            return Command::FAILURE;
        }

        file_put_contents($configPath, $this->generateConfig($name, $domain, $database));
        $console->success('Fichier ' . self::CONFIG_FILE . ' généré (application : ' . $name . ')');

        $this->checkDockerFiles($console, $basePath);

        $console->section('Étapes suivantes');
        $console->listing([
            'Préparer le serveur : frankendeploy server add prod deploy@mon-serveur.fr',
            'Puis : frankendeploy server setup prod --email moi@exemple.fr',
            'Vérifier : frankendeploy doctor prod',
            'Déployer : frankendeploy deploy prod',
        ]);

        return Command::SUCCESS;
    }

    /**
     * Nom de l'application : option --name, sinon nom du dossier du projet
     * Dans le conteneur Docker de développement, le dossier du projet est /app :
     * le nom n'est pas déduit, sinon tous les projets s'appelleraient "app" sur le serveur
     * @param string|null $option Valeur de l'option --name
     * @param string $basePath Dossier du projet
     * @return string Nom formaté, vide si invalide ou impossible à déduire
     */
    private function resolveName(?string $option, string $basePath): string
    {
        if ($option !== null) {
            return $this->formatName($option);
        }

        $folder = basename((string) realpath($basePath));
        return $folder === 'app' ? '' : $this->formatName($folder);
    }

    /**
     * Vérifie la présence des fichiers Docker du framework nécessaires à l'image de production
     * Sans eux, FrankenDeploy génère des fichiers prévus pour Symfony (bin/console),
     * incompatibles avec Edu-Framework
     * @param SymfonyStyle $console Sortie de la commande
     * @param string $basePath Dossier du projet
     * @return void
     */
    private function checkDockerFiles(SymfonyStyle $console, string $basePath): void
    {
        $missing = array_filter(
            self::REQUIRED_FILES,
            static fn (string $file): bool => file_exists($basePath . $file) === false
        );
        $hasProdStage = $missing === []
            && str_contains((string) file_get_contents($basePath . 'Dockerfile'), 'AS frankenphp_prod');
        if ($hasProdStage === true) {
            return;
        }

        $console->warning(
            ($missing !== []
                ? 'Fichiers manquants pour construire l\'image : ' . implode(', ', $missing) . '. '
                : 'Le Dockerfile ne contient pas de stage "frankenphp_prod", requis par FrankenDeploy. ')
            . 'Copiez les fichiers ' . implode(', ', self::REQUIRED_FILES)
            . ' depuis vendor/studoo/edu-framework/ (voir la doc "Déployer son application").'
        );
    }

    /**
     * Formate le nom de l'application au format FrankenDeploy (minuscules, chiffres, tirets)
     * @param string $name Nom brut
     * @return string
     */
    private function formatName(string $name): string
    {
        $name = strtolower($name);
        $name = (string) preg_replace('/[^a-z0-9]+/', '-', $name);
        return trim($name, '-');
    }

    /**
     * Génère le contenu du fichier frankendeploy.yaml
     * @param string $name Nom de l'application
     * @param string|null $domain Nom de domaine
     * @param string $database Base de données
     * @return string
     */
    private function generateConfig(string $name, ?string $domain, string $database): string
    {
        $domainLine = $domain === null
            ? "  # domain: mon-app.fr          # à renseigner : sans domaine, l'app n'est pas exposée"
            : '  domain: ' . strtolower($domain);

        $databaseBlock = match ($database) {
            'mariadb', 'mysql' => <<<YAML

                # Base de données gérée par FrankenDeploy (conteneur sur le serveur)
                # FrankenDeploy injecte DATABASE_URL, Edu-Framework en déduit les variables DB_*
                database:
                  driver: {$database}
                  version: "{$this->databaseVersion($database)}"
                  managed: true

                YAML,
            'sqlite' => <<<YAML

                # Base SQLite : fichier stocké dans var/sqlite (dossier partagé)
                # Variables à définir (une commande par variable) :
                #   frankendeploy env set prod DB_HOST_STATUS=true
                #   frankendeploy env set prod DB_TYPE=sqlite
                #   frankendeploy env set prod DB_NAME=app_db
                database:
                  driver: sqlite
                  path: var/sqlite/app_db.sqlite

                YAML,
            default => "\n",
        };

        return <<<YAML
            # Configuration de déploiement Edu-Framework avec FrankenDeploy
            # Généré par : php bin/edu make:deploy
            # Doc Edu-Framework : https://studoo-app.github.io/edu-framework/deploy/
            # Doc FrankenDeploy  : https://yoanbernabeu.github.io/frankendeploy/config/project/
            #
            # L'image de production est construite avec le Dockerfile du projet
            # (stage frankenphp_prod) : FrankenDeploy ne le régénère pas.
            # Ne lancez pas frankendeploy build, init --force ou dev : ces commandes écrasent
            # Dockerfile, docker-entrypoint.sh, .dockerignore et compose.yaml par des versions Symfony.
            # Les secrets ne vont jamais dans ce fichier : frankendeploy env set prod CLE=valeur

            name: {$name}

            php:
              version: "8.4"
              extensions:
                - pdo_sqlite
                - pdo_mysql
                - mbstring
                - zip
                - intl
                - opcache
            {$databaseBlock}
            deploy:
            {$domainLine}
              # Page testée avant de basculer le trafic : doit répondre 200 sans connexion.
              # Si vous la changez, changez aussi la ligne HEALTHCHECK du Dockerfile.
              healthcheck_path: /
              keep_releases: 5
              # Fichiers et dossiers conservés d'un déploiement à l'autre
              # (tout ce qui est écrit ailleurs disparaît au déploiement suivant)
              shared_files:
                - .env.local
              shared_dirs:
                - var/logs
                - var/sessions
                - var/sqlite
                - public/upload

            YAML;
    }

    /**
     * Version de l'image de base de données
     * @param string $database Base de données
     * @return string
     */
    private function databaseVersion(string $database): string
    {
        return (string) self::DATABASES[$database];
    }
}
