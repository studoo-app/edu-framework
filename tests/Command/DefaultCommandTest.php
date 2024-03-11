<?php

namespace Command;

use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Commands\DefaultCommand;
use Studoo\EduFramework\Commands\Extends\CommandBanner;
use Studoo\EduFramework\Core\ConfigCore;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class DefaultCommandTest
 * Test de la commande par défaut (\Studoo\EduFramework\Commands\DefaultCommand)
 * Example command line:
 * ```
 * $ php bin/edu default
 * ```
 */
class DefaultCommandTest extends TestCase
{
    private $commandeTester;

    protected function setUp(): void
    {
        (new ConfigCore([]));
        $application = new Application(ConfigCore::getConfig('name'), ConfigCore::getConfig('version'));
        CommandBanner::setVersion($application->getVersion());
        $application->add(new DefaultCommand());
        $command = $application->find("default");
        $this->commandeTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        $this->commandeTester = null;
    }

    public function testCommandDefault(): void
    {
        $this->commandeTester->execute([]);
        $output = $this->commandeTester->getDisplay();

        $this->assertStringContainsString('Check votre env. :', $output);
        $this->assertStringContainsString('Liste des commandes :', $output);
    }

}
