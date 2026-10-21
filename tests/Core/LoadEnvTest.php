<?php

namespace Core;

use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Core\ConfigCore;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Chargement des variables d'environnement (ConfigCore::loadEnv)
 * utilisé en développement (.env) comme en production (environnement Docker, .env.local)
 */
class LoadEnvTest extends TestCase
{
    private const KEYS = [
        'EDU_TEST_VAR', 'EDU_TEST_LOCAL', 'DATABASE_URL', 'DB_HOST_STATUS', 'DB_TYPE',
        'DB_HOST', 'DB_SOCKET', 'DB_USER', 'DB_PASSWORD', 'DB_NAME',
    ];

    /**
     * Sauvegarde des variables globales modifiées par les tests
     * @var array<string, mixed>
     */
    private array $backupEnv = [];

    private string $envPath;

    protected function setUp(): void
    {
        $this->envPath = sys_get_temp_dir() . '/edu-framework-test-env-' . uniqid() . '/';
        mkdir($this->envPath);

        foreach (self::KEYS as $key) {
            if (array_key_exists($key, $_ENV) === true) {
                $this->backupEnv[$key] = $_ENV[$key];
            }
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);
        }
        (new ConfigCore([]));
    }

    protected function tearDown(): void
    {
        foreach (self::KEYS as $key) {
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);
        }
        foreach ($this->backupEnv as $key => $value) {
            $_ENV[$key] = $value;
        }
        (new Filesystem())->remove($this->envPath);
    }

    public function testSansFichierEnv(): void
    {
        // En production, l'image Docker ne contient pas de .env : pas d'exception
        ConfigCore::loadEnv($this->envPath);
        $this->assertFalse(ConfigCore::existEnv('EDU_TEST_VAR'));
    }

    public function testFichierEnv(): void
    {
        file_put_contents($this->envPath . '.env', "EDU_TEST_VAR=depuis-env\n");
        ConfigCore::loadEnv($this->envPath);
        $this->assertEquals('depuis-env', ConfigCore::getEnv('EDU_TEST_VAR'));
    }

    public function testEnvLocalPrioritaireSurEnv(): void
    {
        file_put_contents($this->envPath . '.env', "EDU_TEST_VAR=depuis-env\nEDU_TEST_LOCAL=env\n");
        file_put_contents($this->envPath . '.env.local', "EDU_TEST_VAR=depuis-env-local\n");
        ConfigCore::loadEnv($this->envPath);

        $this->assertEquals('depuis-env-local', ConfigCore::getEnv('EDU_TEST_VAR'));
        // Les variables absentes du .env.local proviennent du .env
        $this->assertEquals('env', ConfigCore::getEnv('EDU_TEST_LOCAL'));
    }

    public function testEnvironnementProcessus(): void
    {
        // Variable passée par Docker (docker run -e) : absente de $_ENV
        putenv('EDU_TEST_VAR=depuis-docker');
        $this->assertTrue(ConfigCore::existEnv('EDU_TEST_VAR'));
        $this->assertEquals('depuis-docker', ConfigCore::getEnv('EDU_TEST_VAR'));
    }

    public function testEnvironnementProcessusPrioritaireSurFichiers(): void
    {
        // Variable visible uniquement par getenv() (php -S ou mod_php avec variables_order=GPCS)
        putenv('EDU_TEST_VAR=depuis-docker');
        file_put_contents($this->envPath . '.env.local', "EDU_TEST_VAR=depuis-env-local\n");
        ConfigCore::loadEnv($this->envPath);

        $this->assertEquals('depuis-docker', ConfigCore::getEnv('EDU_TEST_VAR'));
    }

    public function testVariableAbsente(): void
    {
        $this->assertFalse(ConfigCore::existEnv('EDU_TEST_VAR'));
        $this->assertEquals('', ConfigCore::getEnv('EDU_TEST_VAR'));
    }

    public function testDatabaseUrl(): void
    {
        // Format de l'URL générée par FrankenDeploy pour une base MariaDB gérée
        putenv('DATABASE_URL=mysql://mon-app:5f2b%40c9@mon-app-db:3306/mon_app?serverVersion=11-MariaDB&charset=utf8mb4');
        ConfigCore::loadEnv($this->envPath);

        $this->assertEquals('true', ConfigCore::getEnv('DB_HOST_STATUS'));
        $this->assertEquals('mysql', ConfigCore::getEnv('DB_TYPE'));
        $this->assertEquals('mon-app-db', ConfigCore::getEnv('DB_HOST'));
        $this->assertEquals('3306', ConfigCore::getEnv('DB_SOCKET'));
        $this->assertEquals('mon-app', ConfigCore::getEnv('DB_USER'));
        $this->assertEquals('5f2b@c9', ConfigCore::getEnv('DB_PASSWORD'));
        $this->assertEquals('mon_app', ConfigCore::getEnv('DB_NAME'));
    }

    public function testDatabaseUrlNEcrasePasLesVariablesDb(): void
    {
        putenv('DATABASE_URL=mysql://user:pass@db-host:3306/db_name');
        file_put_contents($this->envPath . '.env.local', "DB_NAME=autre_base\nDB_HOST_STATUS=false\n");
        ConfigCore::loadEnv($this->envPath);

        $this->assertEquals('autre_base', ConfigCore::getEnv('DB_NAME'));
        $this->assertEquals('false', ConfigCore::getEnv('DB_HOST_STATUS'));
        $this->assertEquals('db-host', ConfigCore::getEnv('DB_HOST'));
    }

    public function testDatabaseUrlPostgresql(): void
    {
        putenv('DATABASE_URL=postgresql://user:pass@db-host:5432/db_name?serverVersion=16');
        ConfigCore::loadEnv($this->envPath);

        $this->assertEquals('pgsql', ConfigCore::getEnv('DB_TYPE'));
        $this->assertEquals('5432', ConfigCore::getEnv('DB_SOCKET'));
    }

    public function testDatabaseUrlSchemaInconnu(): void
    {
        putenv('DATABASE_URL=sqlite:///var/data.db');
        ConfigCore::loadEnv($this->envPath);

        $this->assertFalse(ConfigCore::existEnv('DB_TYPE'));
        $this->assertFalse(ConfigCore::existEnv('DB_HOST_STATUS'));
    }

    public function testDatabaseUrlTronqueeParUnDiese(): void
    {
        // FrankenDeploy n'entoure pas de guillemets une valeur contenant # :
        // phpdotenv coupe la valeur au # (mysql://app:Xy7), l'erreur doit être explicite
        file_put_contents($this->envPath . '.env.local', "DATABASE_URL=mysql://app:Xy7#kP@db-host:3306/app\n");

        $this->expectException(\Studoo\EduFramework\Core\Exception\ErrorConfigException::class);
        $this->expectExceptionMessage('DATABASE_URL est invalide');
        ConfigCore::loadEnv($this->envPath);
    }

    public function testDatabaseUrlMotDePasseEncode(): void
    {
        file_put_contents($this->envPath . '.env.local', "DATABASE_URL=mysql://app:Xy7%23kP@db-host:3306/app\n");
        ConfigCore::loadEnv($this->envPath);

        $this->assertEquals('Xy7#kP', ConfigCore::getEnv('DB_PASSWORD'));
        $this->assertEquals('db-host', ConfigCore::getEnv('DB_HOST'));
    }
}
