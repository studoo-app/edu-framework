<?php

namespace Studoo\EduFramework\Commands\Extends;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class ListCommand
 * Liste des commandes disponibles
 *
 * @author Benoit Foujols
 */
class listCommand
{
    private $output;
    private $symfonyStyle;
    private $listCommand = [
        ["make:controller", "Création d'un controller (classe, config, template)"]
    ];

    public function __construct(OutputInterface $output, SymfonyStyle $symfonyStyle)
    {
        $this->output = $output;
        $this->symfonyStyle = $symfonyStyle;
    }

    /**
     * Rendu des prérequis dans le terminal
     *
     * @return void
     */
    public function render()
    {
        $this->symfonyStyle->writeln([
            'Liste des commandes : ',
        ]);
        $table = new Table($this->output);
        $table
            ->setHeaders(['COMMANDE', 'DESCRIPTION'])
            ->setRows($this->listCommand);
        $table->render();
        $this->symfonyStyle->writeln([
            '',
        ]);
    }
}
