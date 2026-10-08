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
use Studoo\EduFramework\Commands\Exception\EntityAlreadyExistsException;
use Studoo\EduFramework\Commands\Extends\CommandBanner;
use Studoo\EduFramework\Commands\Extends\CommandManage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class CreateEntityCommand
 * Classe permettant l'utilisation de la commande php bin/edu make:entity <entity-name>
 * Cette commande permet de générer une entité et son repository
 * afin d'éviter de faire des copier/coller
 * Example command line:
 * ```
 * $ php bin/edu make:entity ville
 * $ php bin/edu make:entity ville --fields "nom:string,code_postal:string"
 * ```
 * @package Studoo\EduFramework\Commands
 */
#[AsCommand(
    name: 'make:entity',
    description: 'Génération d une entité et son repository',
)]
class CreateEntityCommand extends CommandManage
{
    private const ENTITY_DIR = './app/Entity/';

    private const REPOSITORY_DIR = './app/Repository/';

    /**
     * Les types autorisés pour les champs de l'entité
     */
    private const TYPES_VALIDES = ['string', 'int', 'float', 'bool'];

    protected function configure(): void
    {
        $this->addArgument('entity-name', InputArgument::REQUIRED, 'Entity name');
        $this->addOption(
            'fields',
            'f',
            InputOption::VALUE_REQUIRED,
            'Liste des champs de l\'entité Exemple: "nom:string,code_postal:string"',
            null
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws EntityAlreadyExistsException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        self::$stdOutput->writeln([
            CommandBanner::getBanner(),
            'Génération d une entité et son repository',
            ''
        ]);

        // Format entity-name arg
        $namesCollection = $this->getNamesCollection($input->getArgument('entity-name'));

        try {
            // Récupération des champs de l'entité
            // Par l'option --fields Exemple: "nom:string,code_postal:string"
            // Ou en mode interactif si l'option est absente
            $fields = $this->getFields($input);
        } catch (\InvalidArgumentException $exception) {
            self::$stdOutput->error($exception->getMessage());
            return Command::FAILURE;
        }

        if (count($fields) === 0) {
            self::$stdOutput->error("Aucun champ défini pour l'entité <" . $namesCollection['className'] . ">");
            return Command::FAILURE;
        }

        // On vérifie que l'entité et le repository n'existent pas déjà
        // avant de générer les fichiers (pour éviter un état partiel)
        if (file_exists(self::ENTITY_DIR . $namesCollection['className'] . '.php') === true
            || file_exists(self::REPOSITORY_DIR . $namesCollection['className'] . 'Repository.php') === true) {
            throw new EntityAlreadyExistsException();
        }

        // Génération de l'entité dans app/Entity/
        $this->generateEntity($namesCollection['className'], $fields);
        // Génération du repository dans app/Repository/
        $this->generateRepository($namesCollection['className'], $namesCollection['table'], $fields);

        self::$stdOutput->success("Entity successfully generated");
        self::$stdOutput->writeln([
            '      ' . self::ENTITY_DIR . $namesCollection['className'] . '.php',
            '      ' . self::REPOSITORY_DIR . $namesCollection['className'] . 'Repository.php'
        ]);

        return Command::SUCCESS;
    }

    /**
     * Fonction permettant le traitement de l'arg entity-name passé à la commande
     * afin de générer les différents noms et chemins nécessaires
     * @param string $arg Nom de l'entité Exemple: ville
     * @return array
     */
    private function getNamesCollection(string $arg): array
    {
        return [
            // Le nom de la classe de l'entité Exemple: Ville
            'className' => ucfirst($arg),
            // Le nom de la table dans la base de données Exemple: ville
            'table' => strtolower($arg)
        ];
    }

    /**
     * Fonction permettant de récupérer les champs de l'entité
     * Soit par l'option --fields Exemple: "nom:string,code_postal:string"
     * Soit en mode interactif si l'option est absente
     * @param InputInterface $input
     * @return array<mixed> Tableau des champs [['name' => ..., 'type' => ...], ...]
     * @throws \InvalidArgumentException Si un champ ou un type est invalide
     */
    private function getFields(InputInterface $input): array
    {
        $fields = [];
        $fieldsOption = $input->getOption('fields');

        if ($fieldsOption !== null) {
            // Mode direct Exemple: --fields "nom:string,code_postal:string"
            foreach (explode(',', $fieldsOption) as $field) {
                $parts = explode(':', trim($field));
                if (count($parts) !== 2) {
                    throw new \InvalidArgumentException(
                        'Format des champs invalide. Exemple: "nom:string,code_postal:string"'
                    );
                }
                $fields[] = $this->validateField(trim($parts[0]), trim($parts[1]));
            }
            return $fields;
        }

        // Mode interactif : l'utilisateur saisit les champs un par un
        // Une valeur vide pour le nom du champ met fin à la saisie
        while (true) {
            $name = self::$stdOutput->ask("Nom du champ (vide pour terminer)");
            if ($name === null || trim($name) === '') {
                break;
            }
            $type = self::$stdOutput->ask(
                'Type (' . implode(', ', self::TYPES_VALIDES) . ')',
                'string',
                function (string $value): string {
                    if (in_array($value, self::TYPES_VALIDES, true) === false) {
                        throw new \InvalidArgumentException(
                            'Type invalide. Types autorisés : ' . implode(', ', self::TYPES_VALIDES)
                        );
                    }
                    return $value;
                }
            );
            $fields[] = $this->validateField(trim($name), $type);
        }
        return $fields;
    }

    /**
     * Vérifie qu'un champ est valide : nom d'identifiant PHP et type autorisé
     * @param string $name Le nom du champ
     * @param string $type Le type du champ
     * @return array<string> Le champ validé ['name' => ..., 'type' => ...]
     * @throws \InvalidArgumentException
     */
    private function validateField(string $name, string $type): array
    {
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name) === 0) {
            throw new \InvalidArgumentException("Le nom du champ <$name> est invalide");
        }
        if (in_array($type, self::TYPES_VALIDES, true) === false) {
            throw new \InvalidArgumentException(
                "Le type <$type> du champ <$name> est invalide. Types autorisés : "
                . implode(', ', self::TYPES_VALIDES)
            );
        }
        return ['name' => $name, 'type' => $type];
    }

    /**
     * Transforme un nom de champ snake_case en camelCase
     * Exemple: code_postal -> codePostal
     * @param string $name Le nom du champ en snake_case
     * @return string Le nom de la méthode en camelCase
     */
    private function toCamelCase(string $name): string
    {
        return lcfirst(str_replace('_', '', ucwords($name, '_')));
    }

    /**
     * Fonction permettant de générer la classe de l'entité dans app/Entity/
     * L'entité possède un identifiant (id), un constructeur, des getters et des setters fluides
     * @param string $className Le nom de la classe Exemple: Ville
     * @param array<mixed> $fields Les champs de l'entité
     * @return void
     */
    private function generateEntity(string $className, array $fields): void
    {
        if (is_dir(self::ENTITY_DIR) === false) {
            mkdir(self::ENTITY_DIR);
        }

        $file = new PhpFile();
        $namespace = $file->addNamespace("Entity");
        $class = $namespace->addClass($className);

        // L'identifiant est attribué par la base de données
        $class->addProperty('id')->setPrivate()->setType('int');

        // Le constructeur hydrate l'entité avec tous ses champs
        $constructeur = $class->addMethod('__construct');
        $constructeur->addParameter('id')->setType('int');
        $corpsConstructeur = ['$this->id = $id;'];

        foreach ($fields as $field) {
            $class->addProperty($field['name'])->setPrivate()->setType($field['type']);
            $constructeur->addParameter($field['name'])->setType($field['type']);
            $corpsConstructeur[] = '$this->' . $field['name'] . ' = $' . $field['name'] . ';';
        }
        $constructeur->setBody(implode("\n", $corpsConstructeur));

        // Pas de setter pour l'identifiant : il est attribué par la base de données
        $getId = $class->addMethod('getId');
        $getId->setReturnType('int');
        $getId->addBody('return $this->id;');

        foreach ($fields as $field) {
            // Getter Exemple: getCodePostal(): string
            $getter = $class->addMethod('get' . ucfirst($this->toCamelCase($field['name'])));
            $getter->setReturnType($field['type']);
            $getter->addBody('return $this->' . $field['name'] . ';');

            // Setter fluide Exemple: setCodePostal(string $codePostal): self
            $setter = $class->addMethod('set' . ucfirst($this->toCamelCase($field['name'])));
            $setter->addParameter($field['name'])->setType($field['type']);
            $setter->setReturnType('self');
            $setter->addBody('$this->' . $field['name'] . ' = $' . $field['name'] . ';');
            $setter->addBody('return $this;');
        }

        file_put_contents(self::ENTITY_DIR . "$className.php", $file);
    }

    /**
     * Fonction permettant de générer la classe du repository dans app/Repository/
     * Le repository regroupe les traitements sur la table de l'entité
     * @param string $className Le nom de la classe de l'entité Exemple: Ville
     * @param string $table Le nom de la table dans la base de données Exemple: ville
     * @param array<mixed> $fields Les champs de l'entité
     * @return void
     */
    private function generateRepository(string $className, string $table, array $fields): void
    {
        if (is_dir(self::REPOSITORY_DIR) === false) {
            mkdir(self::REPOSITORY_DIR);
        }

        $file = new PhpFile();
        $namespace = $file->addNamespace("Repository");
        $namespace->addUse("Entity\\" . $className);
        $namespace->addUse('Studoo\EduFramework\Core\Service\DatabaseService');
        $class = $namespace->addClass($className . "Repository");

        // Méthode de lecture Exemple: getVilles(): array
        $methode = $class->addMethod('get' . $className . 's');
        $methode->setReturnType('array');

        // Les casts évitent les erreurs de type avec les bases de données
        // qui renvoient des chaines de caractères (Exemple: SQLite)
        $castType = function (string $type, string $expression): string {
            return match ($type) {
                'int' => "(int) $expression",
                'float' => "(float) $expression",
                'bool' => "(bool) $expression",
                default => $expression,
            };
        };

        // Hydratation de l'entité Exemple: new Ville((int) $row['id'], $row['nom'])
        $hydratation = [$castType('int', "\$row['id']")];
        foreach ($fields as $field) {
            $hydratation[] = $castType($field['type'], "\$row['" . $field['name'] . "']");
        }

        $collection = '$' . strtolower($className) . 's';

        $methode->setBody(
            $collection . " = [];\n"
            . "\n"
            . "\$dataService = DatabaseService::getConnect();\n"
            . "\$stmt = \$dataService->query('SELECT * FROM " . $table . "');\n"
            . "\n"
            . "while (\$row = \$stmt->fetch()) {\n"
            . "    " . $collection . "[] = new " . $className . "(" . implode(', ', $hydratation) . ");\n"
            . "}\n"
            . "\n"
            . "return " . $collection . ";"
        );

        file_put_contents(self::REPOSITORY_DIR . "$className" . "Repository.php", $file);
    }
}
