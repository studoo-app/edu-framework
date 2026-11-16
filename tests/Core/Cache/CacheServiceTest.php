<?php

namespace Core\Cache;

use PHPUnit\Framework\TestCase;
use Predis\ClientInterface;
use Studoo\EduFramework\Core\Cache\CacheService;
use Studoo\EduFramework\Core\Cache\FileCache;
use Studoo\EduFramework\Core\Cache\NullCache;
use Studoo\EduFramework\Core\Cache\RedisCache;
use Studoo\EduFramework\Core\Cache\TraceableCache;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Exception\ErrorCacheException;
use Studoo\EduFramework\Core\Exception\ErrorConfigException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Création du cache depuis le .env (CacheFactory) et point d'accès CacheService
 */
class CacheServiceTest extends TestCase
{
    private const KEYS = [
        'APP_ENV', 'CACHE_DRIVER', 'CACHE_PREFIX', 'CACHE_TTL', 'REDIS_HOST', 'REDIS_PORT', 'REDIS_DB',
    ];

    /**
     * Sauvegarde des variables d'environnement modifiées par les tests
     * @var array<string, mixed>
     */
    private array $backupEnv = [];

    private string $cachePath;

    /**
     * Retire les variables testées de l'environnement
     * @return void
     */
    protected function setUp(): void
    {
        foreach (self::KEYS as $key) {
            if (array_key_exists($key, $_ENV) === true) {
                $this->backupEnv[$key] = $_ENV[$key];
            }
            unset($_ENV[$key]);
            putenv($key);
        }
        $this->cachePath = sys_get_temp_dir() . '/edu-framework-test-service-' . uniqid() . '/';
        (new ConfigCore(['cache_path' => $this->cachePath]));
        CacheService::setCache(null);
    }

    /**
     * Restaure les variables d'environnement
     * @return void
     */
    protected function tearDown(): void
    {
        foreach (self::KEYS as $key) {
            unset($_ENV[$key]);
        }
        foreach ($this->backupEnv as $key => $value) {
            $_ENV[$key] = $value;
        }
        CacheService::setCache(null);
        (new Filesystem())->remove($this->cachePath);
    }

    /**
     * Sans configuration, le driver fichier est utilisé (aucun serveur nécessaire)
     * @return void
     */
    public function testDriverFichierParDefaut(): void
    {
        $cache = CacheService::getCache();

        $this->assertInstanceOf(FileCache::class, $cache);
        $cache->set('ville:list', ['Paris']);
        $this->assertDirectoryExists($this->cachePath . 'data');
    }

    /**
     * Le cache est créé une seule fois
     * @return void
     */
    public function testInstanceUnique(): void
    {
        $this->assertSame(CacheService::getCache(), CacheService::getCache());
    }

    /**
     * CACHE_DRIVER=none désactive le cache
     * @return void
     */
    public function testDriverNone(): void
    {
        $_ENV['CACHE_DRIVER'] = 'none';

        $this->assertInstanceOf(NullCache::class, CacheService::getCache());
    }

    /**
     * CACHE_DRIVER=redis crée un client Predis, sans se connecter tant qu'aucune commande n'est envoyée
     * @return void
     */
    public function testDriverRedis(): void
    {
        $_ENV['CACHE_DRIVER'] = 'redis';
        $_ENV['REDIS_HOST'] = 'redis.invalid';
        $_ENV['CACHE_PREFIX'] = 'mon-app:';

        $this->assertInstanceOf(RedisCache::class, CacheService::getCache());
        $redis = CacheService::getRedis();
        $this->assertInstanceOf(ClientInterface::class, $redis);
        $this->assertSame('mon-app:', $redis->getOptions()->prefix->getPrefix());
        $this->assertSame('redis.invalid', $redis->getConnection()->getParameters()->host);
    }

    /**
     * En mode dev, le cache est traçable (barre de debug) et getRedis() fonctionne toujours
     * @return void
     */
    public function testModeDevTracable(): void
    {
        $_ENV['APP_ENV'] = 'dev';
        $_ENV['CACHE_DRIVER'] = 'redis';

        $this->assertInstanceOf(TraceableCache::class, CacheService::getCache());
        $this->assertInstanceOf(ClientInterface::class, CacheService::getRedis());
    }

    /**
     * getRedis() nécessite le driver redis
     * @return void
     */
    public function testRedisSansDriverRedis(): void
    {
        $this->expectException(ErrorCacheException::class);
        $this->expectExceptionMessage('CACHE_DRIVER=redis');
        CacheService::getRedis();
    }

    /**
     * Un driver inconnu lève une erreur de configuration qui liste les valeurs possibles
     * @return void
     */
    public function testDriverInconnu(): void
    {
        $_ENV['CACHE_DRIVER'] = 'memcached';

        $this->expectException(ErrorConfigException::class);
        $this->expectExceptionMessage('none, file, redis');
        CacheService::getCache();
    }

    /**
     * Une durée de vie non numérique lève une erreur de configuration
     * @return void
     */
    public function testTtlInvalide(): void
    {
        $_ENV['CACHE_TTL'] = '1h';

        $this->expectException(ErrorConfigException::class);
        $this->expectExceptionMessage('CACHE_TTL=1h');
        CacheService::getCache();
    }
}
