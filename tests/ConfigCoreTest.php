<?php


use Dotenv\Dotenv;
use Studoo\EduFramework\Core\ConfigCore;
use PHPUnit\Framework\TestCase;

class ConfigCoreTest extends TestCase
{
    public function setUp(): void
    {
        // Gestion du fichier des variables d'environnement (.env)
        $dotenv = Dotenv::createImmutable(__DIR__ . '/Config/');
        $dotenv->load();
        (new ConfigCore([]));
    }

    public function testExistEnvFileTest()
    {
        $this->assertTrue(ConfigCore::existEnv('ENV_UNIT'));
    }

    public function testGetBasePathDefault()
    {
        $this->assertEquals('/', ConfigCore::getConfig('base_path'));
    }

    public function testGetBasePathChangeTo()
    {
        (new ConfigCore(['base_path' => '/test/']));
        $this->assertEquals('/test/', ConfigCore::getConfig('base_path'));
    }

    public function testGetTwigPathDefault()
    {
        $this->assertEquals('/app/Template', ConfigCore::getConfig('twig_path'));
    }

    public function testGetTwigPathChangeTo()
    {
        (new ConfigCore(['twig_path' => '/test/']));
        $this->assertEquals('/test/', ConfigCore::getConfig('twig_path'));
    }

    public function testGetRouteConfigPathDefault()
    {
        $this->assertEquals('/app/Config/', ConfigCore::getConfig('route_config_path'));
    }

    public function testGetRouteConfigPathChangeTo()
    {
        (new ConfigCore(['route_config_path' => '/test/']));
        $this->assertEquals('/test/', ConfigCore::getConfig('route_config_path'));
    }

    public function testGetEnvDbName()
    {
        $_ENV["DB_NAME"] = 'app_db';
        $this->assertEquals('app_db', ConfigCore::getEnv('DB_NAME'));
    }

    public function testGetEnvDbHost()
    {
        $_ENV["DB_HOST"] = '127.0.0.1';
        $this->assertEquals('127.0.0.1', ConfigCore::getEnv('DB_HOST'));
    }

    public function testGetEnvDbSocket()
    {
        $_ENV["DB_SOCKET"] = '3306';
        $this->assertEquals('3306', ConfigCore::getEnv('DB_SOCKET'));
    }

    public function testGetEnvDbType()
    {
        $_ENV["DB_TYPE"] = 'mysql';
        $this->assertEquals('mysql', ConfigCore::getEnv('DB_TYPE'));
    }

    public function testGetEnvDbUser()
    {
        $_ENV["DB_USER"] = 'root';
        $this->assertEquals('root', ConfigCore::getEnv('DB_USER'));
    }

    public function testGetEnvDbPwd()
    {
        $_ENV["DB_PASSWORD"] = 'studoo';
        $this->assertEquals('studoo', ConfigCore::getEnv('DB_PASSWORD'));
    }

    public function testNotExistEnvDbHost()
    {
        $this->assertFalse(ConfigCore::existEnv('DB_HOSTABLE'));
    }

    public function testEduframeYmlFileExist()
    {
        // Le fichier eduframe.yml se trouve à la racine du framework
        $pathEduframe = dirname(__DIR__) . '/eduframe.yml';
        $pathBackup = $pathEduframe . '.bak';

        // Sauvegarde du fichier original puis écriture d'une version personnalisée
        copy($pathEduframe, $pathBackup);
        file_put_contents(
            $pathEduframe,
            "name: 'Mon Framework'\nversion: 'v1.0.0@stable'\ndate_version: '2026-10-08'\nphp_version: '8.4'\n"
        );

        try {
            (new ConfigCore([]));

            $this->assertEquals('Mon Framework', ConfigCore::getConfig('name'));
            $this->assertEquals('v1.0.0@stable', ConfigCore::getConfig('version'));
            $this->assertEquals('2026-10-08', ConfigCore::getConfig('date_version'));
            $this->assertEquals('8.4', ConfigCore::getConfig('php_version'));
        } finally {
            // Restauration du fichier original
            rename($pathBackup, $pathEduframe);
        }
    }

    public function testEduframeYmlFileNotExist()
    {
        // Sans le fichier eduframe.yml, les valeurs par défaut du framework sont conservées
        $pathEduframe = dirname(__DIR__) . '/eduframe.yml';
        $pathBackup = $pathEduframe . '.bak';

        rename($pathEduframe, $pathBackup);

        try {
            (new ConfigCore([]));

            $this->assertEquals('EduFramework', ConfigCore::getConfig('name'));
            $this->assertEquals('v2.5.0', ConfigCore::getConfig('version'));
        } finally {
            // Restauration du fichier original
            rename($pathBackup, $pathEduframe);
        }
    }

    public function testEduframeYmlPartiel()
    {
        // Un fichier avec un seul paramètre : seules les clés présentes sont surchargées
        $pathEduframe = dirname(__DIR__) . '/eduframe.yml';
        $pathBackup = $pathEduframe . '.bak';

        copy($pathEduframe, $pathBackup);
        file_put_contents($pathEduframe, "name: 'Mon Framework'\n");

        try {
            (new ConfigCore([]));

            $this->assertEquals('Mon Framework', ConfigCore::getConfig('name'));
            $this->assertEquals('v2.5.0', ConfigCore::getConfig('version'));
        } finally {
            rename($pathBackup, $pathEduframe);
        }
    }

    public function testEduframeYmlInvalide()
    {
        $pathEduframe = dirname(__DIR__) . '/eduframe.yml';
        $pathBackup = $pathEduframe . '.bak';

        copy($pathEduframe, $pathBackup);
        file_put_contents($pathEduframe, "name: 'unclosed\n");

        $this->expectException(\Studoo\EduFramework\Core\Exception\ErrorConfigException::class);
        $this->expectExceptionMessage('est invalide');

        try {
            new ConfigCore([]);
        } finally {
            // Le bloc finally restaure le fichier original même si l'exception est levée
            rename($pathBackup, $pathEduframe);
        }
    }

    public function testEduframeYmlParamNotString()
    {
        // Piège classique du YAML : php_version: 8.4 est parsé en float et non en chaine
        $pathEduframe = dirname(__DIR__) . '/eduframe.yml';
        $pathBackup = $pathEduframe . '.bak';

        copy($pathEduframe, $pathBackup);
        file_put_contents($pathEduframe, "php_version: 8.4\n");

        $this->expectException(\Studoo\EduFramework\Core\Exception\ErrorConfigException::class);
        $this->expectExceptionMessage('doit être une chaine de caractères');

        try {
            new ConfigCore([]);
        } finally {
            // Le bloc finally restaure le fichier original même si l'exception est levée
            rename($pathBackup, $pathEduframe);
        }
    }
}
