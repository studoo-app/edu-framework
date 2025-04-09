<?php

/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

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
class ListCommand
{
    /**
     * @var OutputInterface $output Interface de sortie
     */
    private OutputInterface $output;

    /**
     * @var SymfonyStyle $symfonyStyle Style de sortie
     */
    private SymfonyStyle $symfonyStyle;

    /**
     * @var array $listCommand Liste des commandes
     */
    private array $listCommand = [
        ["check:config", "Check la configuration des prérequis pour le projet"],
        ["start", "Démarrage du serveur execution du projet"],
        ["list", "Liste des commandes disponibles"]
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
    public function render(): void
    {
        $this->symfonyStyle->writeln([
            'Liste : ',
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
