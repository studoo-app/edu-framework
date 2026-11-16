<?php

namespace Core\Cache;

use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Core\Cache\CacheCollector;
use Studoo\EduFramework\Core\Cache\FileCache;
use Studoo\EduFramework\Core\Cache\NullCache;
use Studoo\EduFramework\Core\Cache\TraceableCache;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Cache traçable (mode dev) et cache désactivé (CACHE_DRIVER=none)
 */
class TraceableCacheTest extends TestCase
{
    private string $directory;

    /**
     * Collecteur vide et dossier temporaire
     * @return void
     */
    protected function setUp(): void
    {
        CacheCollector::reset();
        $this->directory = sys_get_temp_dir() . '/edu-framework-test-traceable-' . uniqid() . '/';
    }

    /**
     * Supprime le dossier temporaire
     * @return void
     */
    protected function tearDown(): void
    {
        CacheCollector::reset();
        (new Filesystem())->remove($this->directory);
    }

    /**
     * Les lectures trouvées (hit), absentes (miss), écritures et suppressions sont comptées
     * @return void
     */
    public function testStatistiques(): void
    {
        $cache = new TraceableCache(new FileCache($this->directory));

        $this->assertSame('défaut', $cache->get('ville:list', 'défaut'));
        $cache->set('ville:list', ['Paris']);
        $this->assertSame(['Paris'], $cache->get('ville:list'));
        $this->assertTrue($cache->has('ville:list'));
        $cache->delete('ville:list');
        $cache->clear();

        $stats = CacheCollector::stats();
        $this->assertSame(2, $stats['hits']);
        $this->assertSame(1, $stats['misses']);
        $this->assertSame(1, $stats['writes']);
        $this->assertSame(2, $stats['deletes']);
        $this->assertCount(6, CacheCollector::all());
        $this->assertSame('ville:list', CacheCollector::all()[0]['key']);
    }

    /**
     * Une clé qui contient null est un hit, pas un miss
     * @return void
     */
    public function testValeurNullEstUnHit(): void
    {
        $cache = new TraceableCache(new FileCache($this->directory));
        $cache->set('vide', null);

        $this->assertNull($cache->get('vide', 'défaut'));
        $this->assertSame(1, CacheCollector::stats()['hits']);
    }

    /**
     * remember() est tracé : un miss et une écriture, puis un hit
     * @return void
     */
    public function testRemember(): void
    {
        $cache = new TraceableCache(new FileCache($this->directory));

        $cache->remember('ville:list', 60, fn () => ['Paris']);
        $cache->remember('ville:list', 60, fn () => ['Lyon']);

        $stats = CacheCollector::stats();
        $this->assertSame(['hits' => 1, 'misses' => 1, 'writes' => 1], array_slice($stats, 0, 3));
        $this->assertSame('file', $cache->getDriverName());
    }

    /**
     * Le cache désactivé n'enregistre rien et calcule toujours la valeur
     * @return void
     */
    public function testNullCache(): void
    {
        $cache = new NullCache();
        $appels = 0;

        $this->assertFalse($cache->set('ville:list', ['Paris']));
        $this->assertFalse($cache->has('ville:list'));
        $this->assertSame('défaut', $cache->get('ville:list', 'défaut'));
        $cache->remember('ville:list', 60, function () use (&$appels) {
            return ++$appels;
        });
        $cache->remember('ville:list', 60, function () use (&$appels) {
            return ++$appels;
        });
        $this->assertSame(2, $appels);
        $this->assertTrue($cache->delete('ville:list'));
        $this->assertTrue($cache->clear());
        $this->assertSame('none', $cache->getDriverName());
    }
}
