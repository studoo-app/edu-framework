<?php

namespace Core\Cache;

use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Core\Cache\CacheInterface;
use Studoo\EduFramework\Core\Exception\ErrorCacheException;

/**
 * Comportement commun à tous les drivers qui stockent les valeurs (file, redis) :
 * chaque classe de test d'un driver hérite de ces tests
 */
abstract class CacheContractTestCase extends TestCase
{
    /**
     * Crée le cache à tester (vide)
     * @param int $defaultTtl Durée de vie par défaut
     * @return CacheInterface
     */
    abstract protected function createCache(int $defaultTtl = 0): CacheInterface;

    /**
     * Une clé absente retourne la valeur par défaut
     * @return void
     */
    public function testCleAbsente(): void
    {
        $cache = $this->createCache();

        $this->assertNull($cache->get('absente'));
        $this->assertSame([], $cache->get('absente', []));
        $this->assertFalse($cache->has('absente'));
    }

    /**
     * Les valeurs JSON (texte, nombres, booléens, tableaux) sont restituées à l'identique
     * @return void
     */
    public function testAllerRetour(): void
    {
        $cache = $this->createCache();
        $villes = [
            ['id' => 1, 'nom' => 'Créteil', 'code_postal' => '94000', 'nombre_habitant' => 92265],
            ['id' => 2, 'nom' => 'Saint-Maur-des-Fossés', 'code_postal' => '94100', 'nombre_habitant' => null],
        ];

        $this->assertTrue($cache->set('ville:list', $villes));
        $this->assertTrue($cache->set('texte', 'Bonjour / à tous'));
        $this->assertTrue($cache->set('decimal', 1.0));
        $this->assertTrue($cache->set('faux', false));

        $this->assertSame($villes, $cache->get('ville:list'));
        $this->assertSame('Bonjour / à tous', $cache->get('texte'));
        $this->assertSame(1.0, $cache->get('decimal'));
        $this->assertFalse($cache->get('faux', 'défaut'));
        $this->assertTrue($cache->has('ville:list'));
    }

    /**
     * Une clé qui contient null existe : get() retourne null et non la valeur par défaut
     * @return void
     */
    public function testValeurNull(): void
    {
        $cache = $this->createCache();
        $cache->set('vide', null);

        $this->assertTrue($cache->has('vide'));
        $this->assertNull($cache->get('vide', 'défaut'));
    }

    /**
     * Un objet est stocké en JSON : il est restitué sous forme de tableau associatif
     * @return void
     */
    public function testObjetDevientTableau(): void
    {
        $cache = $this->createCache();
        $ville = new \stdClass();
        $ville->nom = 'Paris';
        $cache->set('ville:1', $ville);

        $this->assertSame(['nom' => 'Paris'], $cache->get('ville:1'));
    }

    /**
     * Une nouvelle écriture remplace la valeur précédente
     * @return void
     */
    public function testEcrasement(): void
    {
        $cache = $this->createCache();
        $cache->set('compteur', 1);
        $cache->set('compteur', 2);

        $this->assertSame(2, $cache->get('compteur'));
    }

    /**
     * delete() supprime la clé, sans erreur si elle n'existe pas
     * @return void
     */
    public function testSuppression(): void
    {
        $cache = $this->createCache();
        $cache->set('a-supprimer', 'valeur');

        $this->assertTrue($cache->delete('a-supprimer'));
        $this->assertFalse($cache->has('a-supprimer'));
        $this->assertTrue($cache->delete('jamais-creee'));
    }

    /**
     * clear() supprime les clés de l'application
     * @return void
     */
    public function testVidage(): void
    {
        $cache = $this->createCache();
        $cache->set('ville:1', 'Paris');
        $cache->set('ville:2', 'Lyon');

        $this->assertTrue($cache->clear());
        $this->assertFalse($cache->has('ville:1'));
        $this->assertFalse($cache->has('ville:2'));
    }

    /**
     * remember() calcule la valeur une seule fois, y compris lorsqu'elle vaut null
     * @return void
     */
    public function testRemember(): void
    {
        $cache = $this->createCache();
        $appels = 0;
        $calcul = function () use (&$appels) {
            $appels++;
            return ['Paris', 'Lyon'];
        };

        $this->assertSame(['Paris', 'Lyon'], $cache->remember('ville:noms', 60, $calcul));
        $this->assertSame(['Paris', 'Lyon'], $cache->remember('ville:noms', 60, $calcul));
        $this->assertSame(1, $appels);

        $appelsNull = 0;
        $calculNull = function () use (&$appelsNull) {
            $appelsNull++;
            return null;
        };
        $cache->remember('ville:vide', 60, $calculNull);
        $cache->remember('ville:vide', 60, $calculNull);
        $this->assertSame(1, $appelsNull);
    }

    /**
     * Une clé vide est refusée
     * @return void
     */
    public function testCleVide(): void
    {
        $this->expectException(ErrorCacheException::class);
        $this->createCache()->set(' ', 'valeur');
    }

    /**
     * Une durée de vie négative est refusée
     * @return void
     */
    public function testTtlNegatif(): void
    {
        $this->expectException(ErrorCacheException::class);
        $this->createCache()->set('cle', 'valeur', -1);
    }

    /**
     * Une valeur impossible à convertir en JSON lève une erreur explicite
     * @return void
     */
    public function testValeurNonJson(): void
    {
        $this->expectException(ErrorCacheException::class);
        $this->expectExceptionMessage('ne peut pas être convertie en JSON');
        $this->createCache()->set('infini', INF);
    }
}
