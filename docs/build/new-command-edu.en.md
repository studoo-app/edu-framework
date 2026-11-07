# How to build a CLI command?

You can create a new command with the "make:command" command of the "edu" command.

!!! warning "Warning"

    Warning: this feature is available from v2.0

## Why create a CLI command?

A CLI command turns an action of your application into a tool that runs directly in the terminal, without going through the browser. It is useful to:

- **Automate a repetitive task**: generate files (like `make:controller`), clear a cache (`cache:clear`), fill a demo database, run a scheduled job...
- **Make an action scriptable**: commands can be chained in a shell script, a cron job or a CI pipeline — the framework itself works this way (`php bin/edu cache:clear` then `php bin/edu start`)
- **Provide a self-documenting interface**: `php bin/edu liste --help` displays the description and options of the command, without reading its source code
- **Check the result**: a command returns a code (`Command::SUCCESS` or `Command::FAILURE`) that the calling script can test

### The CLI, the natural interface of AI agents

An AI agent cannot click in a graphical interface; on the other hand, it is perfectly able to run a command, read its output and react to its exit code. A well-designed command makes your application **drivable by an agent**:

| Principle | What it changes for the agent |
|----------|-------------------------------|
| A clear description (`#[AsCommand]` attribute) | The agent discovers the command in the `--help` and knows when to use it |
| Explicit options (`--json`) | The agent gets structured output, with no formatting to filter out |
| A correct exit code | The agent knows whether the action succeeded and can decide what to do next |
| Clean output in machine mode | Nothing to parse: no banner, no colors, no extra text |

This is the philosophy behind `make:entity --fields "nom:string,code_postal:string"`: a described command, predictable options — usable both by a developer at the keyboard and by an agent in a script.

## The steps to create a new command

```shell
php bin/edu make:command villeList
```

The command creates the `app/Command/VilleListCommand.php` file and automatically registers the class in the `app/Config/commands.yaml` file:

```yaml
villelist: Command\VilleListCommand
```

### The generated skeleton

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
	    // Add your code here
	    return Command::SUCCESS;
	}
}
```

The skeleton works as is:

```shell
php bin/edu villelist
```

!!! info "The command name"

    It is the `#[AsCommand(name: '...')]` attribute that defines the name typed after `php bin/edu` — the key in the `commands.yaml` file is only a registration identifier.

## A concrete example: a command readable by both humans and agents

Goal: a `ville:list` command that displays the cities in the `ville` table (the one from the [CRUD](dataservice-select.md)) — as a table for a human, as JSON for a script or an AI agent.

### Completing the skeleton

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
    description: 'Lists the cities stored in the database'
)]
class VilleListCommand extends CommandManage
{
    protected function configure(): void
    {
        // --json option: structured output for scripts and AI agents
        $this->addOption('json', null, InputOption::VALUE_NONE, 'Output in JSON format');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        // A command checks its prerequisites and fails cleanly
        if (ConfigCore::getEnv('DB_HOST_STATUS') !== 'true') {
            self::$stdOutput->error('The database is disabled (DB_HOST_STATUS=false in the .env file)');
            return Command::FAILURE;
        }

        $villes = DatabaseService::getConnect()
            ->query('SELECT id, nom, code_postal, nombre_habitant FROM ville')
            ->fetchAll(\PDO::FETCH_ASSOC);

        // Machine output: nothing but the JSON to parse (no banner, no colors)
        if ($input->getOption('json') === true) {
            $output->writeln(json_encode($villes));
            return Command::SUCCESS;
        }

        // Human output: formatted table
        self::$stdOutput->title('List of cities');
        self::$stdOutput->table(
            ['ID', 'Name', 'Postal code', 'Inhabitants'],
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

The key points:

- the invocation name comes from the `#[AsCommand]` attribute (here `ville:list`, in the spirit of `make:entity` or `cache:clear`)
- `configure()` declares the `--json` option: it automatically appears in the command's `--help`
- the exit code (`Command::FAILURE` if the database is disabled) lets a script react without parsing the text
- in `--json` mode, no formatting is added: the caller parses the output directly

### Testing the command

Human output:

```shell
php bin/edu ville:list
```

```text
List of cities
==============

 ID  Name       Postal code  Inhabitants
 1   Paris      75000        2200000
 2   Marseille  13000        800000
 3   Lyon       69000        500000
```

Output for a script or an agent:

```shell
php bin/edu ville:list --json
```

```json
[{"id":"1","nom":"Paris","code_postal":"75000","nombre_habitant":"2200000"},{"id":"2","nom":"Marseille","code_postal":"13000","nombre_habitant":"800000"},{"id":"3","nom":"Lyon","code_postal":"69000","nombre_habitant":"500000"}]
```

### Use by an agent

With this command, an AI agent can answer a factual question without opening a single PHP file:

> **Question asked to the agent**: "How many inhabitants do the stored cities have?"
>
> **Agent**: runs `php bin/edu ville:list --json`, reads the JSON and answers: "3 cities are stored: Paris (2,200,000 inhabitants), Marseille (800,000) and Lyon (500,000)."

The winning deal: you document once (description + options + exit code), and the tool becomes usable by a human in the terminal, a cron script, a CI pipeline... or an AI agent.

## Going further

!!! info "Symfony documentation"

    [https://symfony.com/doc/current/console.html](https://symfony.com/doc/current/console.html){:target="_blank"}
