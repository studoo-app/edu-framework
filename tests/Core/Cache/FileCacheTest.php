<?php

namespace Core\Cache;

use Studoo\EduFramework\Core\Cache\CacheInterface;
use Studoo\EduFramework\Core\Cache\FileCache;
use Symfony\Component\Filesystem\Filesystem;

require_once __DIR__ . '/CacheContractTestCase.php';

/**
 * Driver fichier du cache applicatif (CACHE_DRIVER=file)
 */
class FileCacheTest extends CacheContractTestCase
{
    private string $directory;

    /**
     * Dossier temporaire du cache
     * @return void
     */
    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/edu-framework-test-cache-' . uniqid() . '/data/';
    }

    /**
     * Supprime le dossier temporaire
     * @return void
     */
    protected function tearDown(): void
    {
        (new Filesystem())->remove(dirname($this->directory));
    }

    /**
     * @inheritDoc
     */
    protected function createCache(int $defaultTtl = 0): CacheInterface
    {
        return new FileCache($this->directory, 'edu:', $defaultTtl);
    }

    /**
     * Le dossier est créé à la première écriture et le fichier contient du JSON lisible
     * @return void
     */
    public function testFichierJsonLisible(): void
    {
        $cache = $this->createCache();
        $cache->set('ville:list', [['nom' => 'Créteil']], 0);

        $files = glob($this->directory . '*.cache');
        $this->assertCount(1, $files);
        $entry = json_decode(file_get_contents($files[0]), true);
        $this->assertSame('edu:ville:list', $entry['key']);
        $this->assertSame(0, $entry['expiresAt']);
        $this->assertSame('[{"nom":"Créteil"}]', $entry['value']);
    }

    /**
     * La durée de vie par défaut (CACHE_TTL) est appliquée quand aucune durée n'est donnée
     * @return void
     */
    public function testTtlParDefaut(): void
    {
        $cache = $this->createCache(600);
        $cache->set('ville:list', []);

        $entry = json_decode(file_get_contents(glob($this->directory . '*.cache')[0]), true);
        $this->assertEqualsWithDelta(time() + 600, $entry['expiresAt'], 2);
    }

    /**
     * Une valeur expirée est considérée absente et son fichier est supprimé
     * @return void
     */
    public function testValeurExpiree(): void
    {
        $cache = $this->createCache();
        $cache->set('ville:list', ['Paris'], 60);

        // On fait expirer la valeur en modifiant la date d'expiration du fichier
        $file = glob($this->directory . '*.cache')[0];
        $entry = json_decode(file_get_contents($file), true);
        $entry['expiresAt'] = time() - 1;
        file_put_contents($file, json_encode($entry));

        $this->assertSame('défaut', $cache->get('ville:list', 'défaut'));
        $this->assertFileDoesNotExist($file);
    }

    /**
     * Un fichier corrompu est considéré absent
     * @return void
     */
    public function testFichierCorrompu(): void
    {
        $cache = $this->createCache();
        $cache->set('ville:list', ['Paris']);
        file_put_contents(glob($this->directory . '*.cache')[0], '{"key":');

        $this->assertNull($cache->get('ville:list'));
    }

    /**
     * Deux préfixes différents ne partagent pas les mêmes fichiers
     * @return void
     */
    public function testPrefixe(): void
    {
        (new FileCache($this->directory, 'app1:'))->set('cle', 'app1');
        (new FileCache($this->directory, 'app2:'))->set('cle', 'app2');

        $this->assertSame('app1', (new FileCache($this->directory, 'app1:'))->get('cle'));
        $this->assertSame('app2', (new FileCache($this->directory, 'app2:'))->get('cle'));
    }

    /**
     * Le nom du driver est affiché dans la barre de debug
     * @return void
     */
    public function testNomDuDriver(): void
    {
        $this->assertSame('file', $this->createCache()->getDriverName());
    }
}
