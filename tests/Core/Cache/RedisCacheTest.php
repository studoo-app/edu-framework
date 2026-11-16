<?php

namespace Core\Cache;

use Predis\Client;
use Predis\PredisException;
use Studoo\EduFramework\Core\Cache\CacheInterface;
use Studoo\EduFramework\Core\Cache\RedisCache;
use Studoo\EduFramework\Core\Exception\ErrorCacheException;

require_once __DIR__ . '/CacheContractTestCase.php';

/**
 * Driver Redis du cache applicatif (CACHE_DRIVER=redis)
 * Ces tests nécessitent un serveur Redis : ils sont ignorés s'il est injoignable.
 * Serveur utilisé : EDU_TEST_REDIS_HOST / EDU_TEST_REDIS_PORT (127.0.0.1:6379 par défaut)
 * Exemple : docker run -d --rm -p 6379:6379 redis:8-alpine
 */
class RedisCacheTest extends CacheContractTestCase
{
    /**
     * Préfixe propre à chaque test : les clés des autres applications ne sont jamais touchées
     * @var string
     */
    private string $prefix;

    /**
     * Client Predis sans préfixe, pour observer les clés réellement écrites dans Redis
     * @var Client
     */
    private Client $rawClient;

    /**
     * Vérifie que le serveur Redis de test est joignable
     * @return void
     */
    protected function setUp(): void
    {
        $this->prefix = 'edu-test-' . uniqid() . ':';
        $this->rawClient = new Client(self::parameters());
        try {
            $this->rawClient->ping();
        } catch (PredisException $exception) {
            $this->markTestSkipped('Serveur Redis de test injoignable : ' . $exception->getMessage());
        }
    }

    /**
     * Supprime les clés créées par le test
     * @return void
     */
    protected function tearDown(): void
    {
        if (isset($this->rawClient) === false) {
            return;
        }
        try {
            $keys = $this->rawClient->keys($this->prefix . '*');
            if ($keys !== []) {
                $this->rawClient->del($keys);
            }
            $this->rawClient->del('edu-test-hors-prefixe');
        } catch (PredisException) {
            // Serveur injoignable : test ignoré, rien à nettoyer
        }
    }

    /**
     * @inheritDoc
     */
    protected function createCache(int $defaultTtl = 0): CacheInterface
    {
        return new RedisCache(new Client(self::parameters(), ['prefix' => $this->prefix]), $this->prefix, $defaultTtl);
    }

    /**
     * La clé est écrite avec le préfixe, et la valeur est un JSON lisible (Redis Insight, redis-cli)
     * @return void
     */
    public function testCleAvecPrefixeEtJsonLisible(): void
    {
        $this->createCache()->set('ville:list', [['nom' => 'Créteil']]);

        $this->assertSame('[{"nom":"Créteil"}]', $this->rawClient->get($this->prefix . 'ville:list'));
    }

    /**
     * La durée de vie est transmise à Redis (SET ... EX) ; 0 => pas d'expiration
     * @return void
     */
    public function testDureeDeVie(): void
    {
        $cache = $this->createCache(600);
        $cache->set('avec-ttl', 'valeur', 60);
        $cache->set('ttl-defaut', 'valeur');
        $cache->set('sans-ttl', 'valeur', 0);

        $ttl = $this->rawClient->ttl($this->prefix . 'avec-ttl');
        $this->assertGreaterThan(0, $ttl);
        $this->assertLessThanOrEqual(60, $ttl);
        $this->assertGreaterThan(60, $this->rawClient->ttl($this->prefix . 'ttl-defaut'));
        $this->assertSame(-1, $this->rawClient->ttl($this->prefix . 'sans-ttl'));
    }

    /**
     * clear() ne supprime que les clés du préfixe (pas de FLUSHDB)
     * @return void
     */
    public function testVidageLimiteAuPrefixe(): void
    {
        $this->rawClient->set('edu-test-hors-prefixe', 'autre application');
        $cache = $this->createCache();
        for ($i = 1; $i <= 1200; $i++) {
            $cache->set('ville:' . $i, $i);
        }

        $cache->clear();

        $this->assertSame([], $this->rawClient->keys($this->prefix . '*'));
        $this->assertSame('autre application', $this->rawClient->get('edu-test-hors-prefixe'));
    }

    /**
     * Sans préfixe, clear() est refusé : il viderait toutes les clés du serveur
     * @return void
     */
    public function testVidageSansPrefixeRefuse(): void
    {
        $this->expectException(ErrorCacheException::class);
        (new RedisCache(new Client(self::parameters()), ''))->clear();
    }

    /**
     * Le client retourné utilise le préfixe : une commande Redis directe lit les clés du cache
     * @return void
     */
    public function testClientPartageLePrefixe(): void
    {
        $cache = $this->createCache();
        $cache->set('ville:list', ['Paris']);

        $this->assertSame('["Paris"]', $cache->getClient()->get('ville:list'));
    }

    /**
     * En développement, un serveur injoignable lève une erreur explicite
     * @return void
     */
    public function testServeurInjoignableEnDev(): void
    {
        $cache = new RedisCache(self::unreachableClient(), 'edu:', 0, false, '127.0.0.1:1');

        $this->expectException(ErrorCacheException::class);
        $this->expectExceptionMessage('Le serveur Redis <127.0.0.1:1> est injoignable');
        $cache->get('ville:list');
    }

    /**
     * En production (failSafe), un serveur injoignable se comporte comme un cache vide
     * @return void
     */
    public function testServeurInjoignableEnProduction(): void
    {
        $logFile = tempnam(sys_get_temp_dir(), 'edu-log');
        $previousLog = ini_set('error_log', $logFile);
        try {
            $cache = new RedisCache(self::unreachableClient(), 'edu:', 0, true, '127.0.0.1:1');

            $this->assertSame('défaut', $cache->get('ville:list', 'défaut'));
            $this->assertFalse($cache->set('ville:list', ['Paris']));
            $this->assertSame(['Lyon'], $cache->remember('ville:list', 60, fn () => ['Lyon']));
            // L'erreur n'est journalisée qu'une fois
            $this->assertSame(1, substr_count((string) file_get_contents($logFile), 'injoignable'));
        } finally {
            ini_set('error_log', (string) $previousLog);
            @unlink($logFile);
        }
    }

    /**
     * Paramètres de connexion au serveur Redis de test
     * @return array{host: string, port: int, timeout: float}
     */
    private static function parameters(): array
    {
        return [
            'host' => getenv('EDU_TEST_REDIS_HOST') ?: '127.0.0.1',
            'port' => (int) (getenv('EDU_TEST_REDIS_PORT') ?: 6379),
            'timeout' => 0.5,
        ];
    }

    /**
     * Client Predis vers un port fermé
     * @return Client
     */
    private static function unreachableClient(): Client
    {
        return new Client(['host' => '127.0.0.1', 'port' => 1, 'timeout' => 0.5], ['prefix' => 'edu:']);
    }
}
