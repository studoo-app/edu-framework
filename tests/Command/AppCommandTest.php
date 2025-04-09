<?php

namespace Command;

use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Commands\Extends\AppCommand;
use Symfony\Component\Console\Tester\CommandTester;

class AppCommandTest extends TestCase
{
    public function testCommandDefault(): void
    {
        $_ENV["DB_TYPE"] = "mysql";
        $_ENV["DB_PASSWORD"] = "studoo";
        $_ENV["DB_SOCKET"] = "8006";

        $application = new AppCommand();
        $command = $application->find("default");
        $commandeTester = new CommandTester($command);

        $commandeTester->execute([]);
        $output = $commandeTester->getDisplay();

        $this->assertStringContainsString('Liste :', $output);
    }
}
