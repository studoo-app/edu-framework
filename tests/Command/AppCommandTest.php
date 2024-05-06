<?php

namespace Command;

use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Commands\Extends\AppCommand;
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

        $this->assertStringContainsString('Liste :', $output);
    }
}
