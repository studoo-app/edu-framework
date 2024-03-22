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

    /**
     * @var OutputInterface $output Interface de sortie
     */
    private $output;

    /**
     * @var SymfonyStyle $symfonyStyle Style de sortie
     */
    private $symfonyStyle;

    /**
     * @var array $listCommand Liste des commandes
     */
    private $listCommand = [
        ["make:controller", "Création d'un controller (classe, config, template)"],
        ["check:config", "Check la configuration des prérequis pour le projet"]
    ];

    /**
     * Constructeur
     *
     * @param OutputInterface $output Interface de sortie
     * @param SymfonyStyle $symfonyStyle Style de sortie
     */
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
