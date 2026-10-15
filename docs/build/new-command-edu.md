# Comment construire une commande CLI ?

Vous pouvez créer une nouvelle commande avec la commande "make:command" de la commande "edu".

!!! warning "Attention"

    Attention : cette feature est disponible à partir de v2.0

## Pourquoi créer une commande CLI ?

Une commande CLI transforme une action de votre application en un outil exécutable directement dans le terminal, sans passer par le navigateur. C'est utile pour :

- **Automatiser une tâche répétitive** : générer des fichiers (comme `make:controller`), vider un cache (`cache:clear`), alimenter une base de données de démonstration, lancer un traitement planifié...
- **Rendre une action scriptable** : les commandes s'enchaînent dans un script shell, une tâche cron ou un pipeline CI — le framework fonctionne lui-même ainsi (`php bin/edu cache:clear` puis `php bin/edu start`)
- **Fournir une interface auto-documentée** : `php bin/edu liste --help` affiche la description et les options de la commande, sans lire son code source
- **Vérifier le résultat** : une commande retourne un code (`Command::SUCCESS` ou `Command::FAILURE`) que le script appelant peut tester

### Le CLI, l'interface naturelle des agents IA

Un agent IA ne peut pas cliquer dans une interface graphique ; en revanche, il sait parfaitement exécuter une commande, lire sa sortie et réagir à son code de retour. Une commande bien conçue rend votre application **pilotable par un agent** :

| Principe | Ce que ça change pour l'agent |
|----------|-------------------------------|
| Une description claire (attribut `#[AsCommand]`) | L'agent découvre la commande dans le `--help` et sait quand l'utiliser |
| Des options explicites (`--json`) | L'agent obtient une sortie structurée, sans mise en forme à filtrer |
| Un code de retour correct | L'agent sait si l'action a réussi et peut décider de la suite |
| Une sortie propre en mode machine | Rien à parser : ni bannière, ni couleurs, ni texte superflu |

C'est la philosophie de `make:entity --fields "nom:string,code_postal:string"` : une commande décrite, des options prévisibles — utilisable aussi bien par un développeur au clavier que par un agent dans un script.

## Les étapes pour créer une nouvelle commande

```shell
php bin/edu make:command villeList
```

La commande crée le fichier `app/Command/VilleListCommand.php` et enregistre automatiquement la classe dans le fichier `app/Config/commands.yaml` :

```yaml
villelist: Command\VilleListCommand
```

### Le squelette généré

```php
<?php

namespace Command;

use Studoo\EduFramework\Commands\Extends\CommandBanner;
use Studoo\EduFramework\Core\ConfigCore;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand(name: 'villelist', description: 'Renseigner la description de la commande villelist')]
class VilleListCommand extends \Studoo\EduFramework\Commands\Extends\CommandManage
{
	public function execute(InputInterface $input, OutputInterface $output): int
	{
	     self::$stdOutput->writeln([
	        CommandBanner::getBanner(),
	        'Bienvenue dans la console ' . ConfigCore::getConfig('name'),
	        '',
	    ]);
	    // Ajouter votre code ici
	    return Command::SUCCESS;
	}
}
```

Le squelette fonctionne tel quel :

```shell
php bin/edu villelist
```

!!! info "Le nom de la commande"

    C'est l'attribut `#[AsCommand(name: '...')]` qui définit le nom tapé après `php bin/edu` — la clé du fichier `commands.yaml` n'est qu'un identifiant d'enregistrement.

## Exemple concret : une commande lisible par l'humain et l'agent

Objectif : une commande `ville:list` qui affiche les villes de la table `ville` (celle du [CRUD](dataservice-select.md)) — sous forme de tableau pour un humain, en JSON pour un script ou un agent IA.

### Compléter le squelette

```php
<?php

namespace Command;

use Studoo\EduFramework\Commands\Extends\CommandManage;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Service\DatabaseService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'ville:list',
    description: 'Liste les villes enregistrées dans la base de données'
)]
class VilleListCommand extends CommandManage
{
    protected function configure(): void
    {
        // Option --json : sortie structurée pour les scripts et les agents IA
        $this->addOption('json', null, InputOption::VALUE_NONE, 'Sortie au format JSON');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        // Une commande vérifie ses prérequis et échoue proprement
        if (ConfigCore::getEnv('DB_HOST_STATUS') !== 'true') {
            self::$stdOutput->error('La base de données est désactivée (DB_HOST_STATUS=false dans le fichier .env)');
            return Command::FAILURE;
        }

        $villes = DatabaseService::getConnect()
            ->query('SELECT id, nom, code_postal, nombre_habitant FROM ville')
            ->fetchAll(\PDO::FETCH_ASSOC);

        // Sortie machine : rien d'autre que le JSON à parser (ni bannière, ni couleurs)
        if ($input->getOption('json') === true) {
            $output->writeln(json_encode($villes));
            return Command::SUCCESS;
        }

        // Sortie humaine : tableau mis en forme
        self::$stdOutput->title('Liste des villes');
        self::$stdOutput->table(
            ['ID', 'Nom', 'Code postal', 'Habitants'],
            array_map(
                static fn (array $ville): array => [
                    $ville['id'], $ville['nom'], $ville['code_postal'], $ville['nombre_habitant'],
                ],
                $villes
            )
        );

        return Command::SUCCESS;
    }
}
```

Les points importants :

- le nom d'invocation vient de l'attribut `#[AsCommand]` (ici `ville:list`, dans l'esprit de `make:entity` ou `cache:clear`)
- `configure()` déclare l'option `--json` : elle apparaît automatiquement dans le `--help` de la commande
- le code de retour (`Command::FAILURE` si la base est désactivée) permet à un script de réagir sans parser le texte
- en mode `--json`, aucune mise en forme n'est ajoutée : l'appelant parse directement la sortie

### Tester la commande

Sortie humaine :

```shell
php bin/edu ville:list
```

```text
Liste des villes
================

 ID  Nom        Code postal  Habitants
 1   Paris      75000        2200000
 2   Marseille  13000        800000
 3   Lyon       69000        500000
```

Sortie pour un script ou un agent :

```shell
php bin/edu ville:list --json
```

```json
[{"id":"1","nom":"Paris","code_postal":"75000","nombre_habitant":"2200000"},{"id":"2","nom":"Marseille","code_postal":"13000","nombre_habitant":"800000"},{"id":"3","nom":"Lyon","code_postal":"69000","nombre_habitant":"500000"}]
```

### Utilisation par un agent

Avec cette commande, un agent IA peut répondre à une question factuelle sans ouvrir le moindre fichier PHP :

> **Question posée à l'agent** : « Combien d'habitants ont les villes enregistrées ? »
>
> **Agent** : exécute `php bin/edu ville:list --json`, lit le JSON et répond : « 3 villes sont enregistrées : Paris (2 200 000 habitants), Marseille (800 000) et Lyon (500 000). »

Le contrat gagnant : vous documentez une fois (description + options + code de retour), et l'outil devient utilisable par un humain au terminal, un script cron, un pipeline CI... ou un agent IA.

## Pour aller plus loin

!!! info "Documentation Symfony"

    [https://symfony.com/doc/current/console.html](https://symfony.com/doc/current/console.html){:target="_blank"}
