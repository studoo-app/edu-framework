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
            '<info>Environment PHP : </info>',
        ]);

        $table = new Table($this->output);
        $table
            ->setHeaders(['CHECK', 'SERVICE', 'VERSION'])
            ->setRows($this->run());
        $table->render();
        $this->symfonyStyle->writeln([
            '',
        ]);

        $this->symfonyStyle->writeln([
            '<info>Extension check : </info>',
        ]);

        $this->checkExtension('pdo_mysql');
        $this->checkExtension('mbstring');
        $this->checkExtension('openssl');
        $this->checkExtension('json');
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

    private function checkExtension($extension): void
    {
        if (\extension_loaded($extension)) {
            $this->symfonyStyle->writeln(['  [*] ' . strtoupper($extension) . ' PHP extension est installée.']);
            return;
        }
        $this->symfonyStyle->writeln(['  [X] ' . strtoupper($extension) . ' PHP extension est recommandée.']);
        $extFilename = DIRECTORY_SEPARATOR === '\\' ? 'php_' . $extension . '.dll' : $extension . '.so';
        $extDirs = [
            PHP_EXTENSION_DIR,
            dirname(PHP_BINARY) . DIRECTORY_SEPARATOR . 'ext',
        ];
        foreach ($extDirs as $dir) {
            $extPath = $dir . DIRECTORY_SEPARATOR . $extFilename;
            if (\file_exists($extPath) === false) {
                continue;
            }
            $this->symfonyStyle->writeln(["L'extension existe dans : $extPath"]);
            if (!empty(PHP_CONFIG_FILE_SCAN_DIR) && \is_dir(PHP_CONFIG_FILE_SCAN_DIR)) {
                $this->symfonyStyle->writeln([
                    "\nActiver l'extension dans le fichier de configuration : "
                    . PHP_CONFIG_FILE_SCAN_DIR
                    . DIRECTORY_SEPARATOR
                    . "$extension.ini"
                    . "\ndécocher la ligne suivante :"
                    . "\nextension=$extPath"
                ]);
            } else {
                $this->symfonyStyle->writeln([
                    "\nPour l'activer, éditez votre fichier de configuration php.ini et ajoutez la ligne :"
                    . "\nextension=$extPath"
                ]);
            }
            break;
        }
    }
}
