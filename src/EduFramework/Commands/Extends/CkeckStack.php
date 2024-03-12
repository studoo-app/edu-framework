<?php

namespace Studoo\EduFramework\Commands\Extends;

use Studoo\EduFramework\Core\ConfigCore;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class CheckStack
 * Gestion des prerequis
 *
 * @author Benoit Foujols
 */
class CkeckStack
{
    /**
     * @var OutputInterface
     */
    private OutputInterface $output;

    /**
     * @var SymfonyStyle
     */
    private SymfonyStyle $symfonyStyle;

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
            'Check votre env. : ',
        ]);
        $table = new Table($this->output);
        $table
            ->setHeaders(['CHECK', 'SERVICE', 'VERSION'])
            ->setRows($this->run());
        $table->render();
        $this->symfonyStyle->writeln([
            '',
        ]);
    }

    /**
     * Execution des tests de prerequis
     *
     * @return array
     */
    private function run(): array
    {
        $listCheck = [];

        $listCheck[] = (version_compare(PHP_VERSION, ConfigCore::getConfig('php_version'), '>=') === true) ? ["OK", 'PHP', PHP_VERSION] : ["KO", 'PHP', PHP_VERSION];
        $listCheck[] = ["INFO", 'PHP', PHP_BINARY];

        return $listCheck;
    }
}
