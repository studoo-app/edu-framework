# Comment construire une commande CLI ?

Vous pouvez créer une nouvelle commande avec la commande "make:command" de la commande "edu".

!!! warning "Attention"

    Attention : cette feature est disponible à partir de v2.0

Pour créer une nouvelle commande, vous devez suivre les instructions suivantes :

## Les étapes pour créer une nouvelle commande

```shell
bin/edu make:command NOM_DE_LA_COMMANDE
```

# Exemple de création d'un controller

```Shell
php bin/edu make:command generate
```

### Le fichier GenerateCommand.php
Cette commande va créer un fichier "GenerateCommand.php" dans le dossier "app/Command". 

```php
<?php

namespace Command;

use Studoo\EduFramework\Commands\Extends\CommandBanner;
use Studoo\EduFramework\Core\ConfigCore;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand(name: 'gererate', description: 'Renseigner la description de la commande gererate')]
class GenerateCommand extends \Studoo\EduFramework\Commands\Extends\CommandManage
{
	public function execute(InputInterface $input, OutputInterface $output): int
	{
		self::$stdOutput->writeln([
		        CommandBanner::getBanner(),
		        'Bienvenue dans la console ' . ConfigCore::getConfig('name'),
		        ''
		    ]);
		    return Command::SUCCESS;
	}
}
```

### Modifier le fichier des commandes
Et modifier le fichier des commandes "config/commands.yaml" pour ajouter la commande de votre controller.

```yaml
gererate: Command\GenerateCommand
```

Vous pouvez maintenant utiliser votre commande en tapant la commande suivante :

```Shell
php bin/edu generate
```

Pour aller plus loin, vous pouvez consulter la documentation de Symfony sur les commandes console :

!!! info "Documentation Symfony"

    [https://symfony.com/doc/current/console.html](https://symfony.com/doc/current/console.html){:target="_blank"}
