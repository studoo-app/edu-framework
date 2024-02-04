<?php

namespace Studoo\EduFramework\Commands\Extends;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CommandManage extends Command
{
    protected static SymfonyStyle $stdOutput;

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        // Debug mode
        if ($output->isVerbose()) {
            error_reporting($output->isDebug() ? E_ALL : E_ALL & ~E_DEPRECATED);
        } elseif ($output->isQuiet()) {
            error_reporting(false);
        }

        self::$stdOutput = new SymfonyStyle($input, $output);
    }

    public static function getStdOutPut(): SymfonyStyle
    {
        return self::$stdOutput;
    }

}
