<?php

namespace Command;

use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Commands\DefaultCommand;
use Studoo\EduFramework\Commands\Extends\AppCommand;
use Studoo\EduFramework\Commands\Extends\CkeckStack;
use Studoo\EduFramework\Commands\Extends\CommandBanner;
use Studoo\EduFramework\Core\ConfigCore;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Tester\CommandTester;

class AppCommandTest extends TestCase
{
    public function testCommandDefault(): void
    {
        $application = new AppCommand();
        $command = $application->find("default");
        $commandeTester = new CommandTester($command);

        $commandeTester->execute([]);
        $output = $commandeTester->getDisplay();

        $this->assertStringContainsString('Check votre env. :', $output);
        $this->assertStringContainsString('Liste des commandes :', $output);
    }
}
