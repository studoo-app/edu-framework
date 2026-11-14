<?php

namespace Core\Profiler\Sql;

use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Core\Profiler\Sql\SqlQueryInspector;

/**
 * Tests de la lecture du texte d'une requête SQL (analyse EXPLAIN du profiler) :
 * tables et alias, jointures, filtres, tris, paramètres et contrôles de sécurité
 */
class SqlQueryInspectorTest extends TestCase
{
    private const string JOIN_QUERY = "SELECT l.titre, CONCAT(a.prenom, ' ', a.nom) AS auteur\n"
        . "FROM livres l\nJOIN auteurs a ON l.auteur_id = a.id\nWHERE a.id = :auteur_id\nORDER BY l.annee_publication";

    /**
     * Vérifie la lecture des tables et de leurs alias (avec ou sans AS, jointures, virgules, backticks)
     */
    public function testTablesAndAliasesAreRead(): void
    {
        $this->assertSame(
            ['livres' => 'livres', 'l' => 'livres', 'auteurs' => 'auteurs', 'a' => 'auteurs'],
            (new SqlQueryInspector(self::JOIN_QUERY))->tables()
        );

        $inspector = new SqlQueryInspector(
            'SELECT * FROM `app_db`.`utilisateurs` AS u LEFT OUTER JOIN emprunts e USING (id), livres '
            . 'WHERE u.id IN (SELECT utilisateur_id FROM retards r)'
        );
        $this->assertSame(['utilisateurs', 'emprunts', 'livres', 'retards'], $inspector->tableNames());
        $this->assertSame('utilisateurs', $inspector->tables()['u']);
        $this->assertSame('retards', $inspector->tables()['r']);
    }

    /**
     * Vérifie la lecture des conditions de jointure et des colonnes filtrées
     */
    public function testJoinConditionsAndFilteredColumnsAreRead(): void
    {
        $inspector = new SqlQueryInspector(self::JOIN_QUERY);

        $this->assertSame([[
            'left_alias' => 'l', 'left_column' => 'auteur_id', 'right_alias' => 'a', 'right_column' => 'id',
        ]], $inspector->getConditions()->joinConditions());
        $this->assertSame([['alias' => 'a', 'column' => 'id', 'operator' => '=']], $inspector->getConditions()->whereColumns());

        $filters = (new SqlQueryInspector(
            "SELECT * FROM livres WHERE annee >= 2000 AND titre LIKE 'Le%' AND auteur_id IN (1, 2) AND isbn IS NULL"
        ))->getConditions()->whereColumns();
        $this->assertSame(['annee', 'titre', 'auteur_id', 'isbn'], array_column($filters, 'column'));
        $this->assertSame(['>=', 'LIKE', 'IN', 'IS'], array_column($filters, 'operator'));
    }

    /**
     * Vérifie la lecture du ORDER BY et du GROUP BY : colonnes, sens du tri et valeurs calculées
     */
    public function testOrderByAndGroupByItemsAreRead(): void
    {
        $inspector = new SqlQueryInspector(
            'SELECT u.id, u.nom, COUNT(e.id) AS total FROM utilisateurs u LEFT JOIN emprunts e ON e.utilisateur_id = u.id '
            . 'GROUP BY u.id, u.nom ORDER BY total DESC, u.nom LIMIT 10'
        );

        $this->assertSame(['total'], $inspector->getOrdering()->selectAliases());
        $order = $inspector->getOrdering()->orderByItems();
        // "total" est un alias du SELECT (valeur calculée) : ce n'est pas une colonne
        $this->assertNull($order[0]['column']);
        $this->assertSame('DESC', $order[0]['direction']);
        $this->assertSame(['alias' => 'u', 'column' => 'nom', 'expression' => 'u.nom', 'direction' => 'ASC'], $order[1]);
        $this->assertSame(['id', 'nom'], array_column($inspector->getOrdering()->groupByItems(), 'column'));
        $this->assertTrue($inspector->hasLimit());
    }

    /**
     * Vérifie la lecture des paramètres nommés (sans doublon) et positionnels
     */
    public function testPlaceholdersAreRead(): void
    {
        $this->assertSame([':auteur_id'], (new SqlQueryInspector(self::JOIN_QUERY))->placeholders());
        $this->assertSame(
            [':debut', ':fin'],
            (new SqlQueryInspector('SELECT * FROM e WHERE d BETWEEN :debut AND :fin OR d = :debut'))->placeholders()
        );
        $this->assertSame([1, 2], (new SqlQueryInspector('SELECT * FROM e WHERE a = ? AND b = ?'))->placeholders());
        // Un ":" ou un "?" dans une chaîne de caractères n'est pas un paramètre
        $this->assertSame([], (new SqlQueryInspector("SELECT * FROM e WHERE t = '10:30 ?'"))->placeholders());
    }

    /**
     * Vérifie les contrôles de sécurité : requête de lecture, requête expliquable, requêtes multiples
     */
    public function testStatementKindAndSafetyChecks(): void
    {
        $select = new SqlQueryInspector('  (SELECT 1)');
        $this->assertSame('SELECT', $select->statementType());
        $this->assertTrue($select->isReadOnly());
        $this->assertTrue($select->isExplainable());

        $this->assertTrue((new SqlQueryInspector('WITH t AS (SELECT 1) SELECT * FROM t'))->isReadOnly());
        $this->assertFalse((new SqlQueryInspector('WITH t AS (SELECT 1) DELETE FROM x'))->isReadOnly());
        $this->assertFalse((new SqlQueryInspector('SELECT * FROM t FOR UPDATE'))->isReadOnly());

        $update = new SqlQueryInspector('UPDATE livres SET titre = :t WHERE id = :id');
        $this->assertFalse($update->isReadOnly());
        $this->assertTrue($update->isExplainable());
        $this->assertSame(['livres'], $update->tableNames());

        $this->assertFalse((new SqlQueryInspector('CREATE TABLE t (id INT)'))->isExplainable());

        $this->assertTrue((new SqlQueryInspector('SELECT 1; DROP TABLE livres'))->hasMultipleStatements());
        $this->assertFalse((new SqlQueryInspector('SELECT 1;'))->hasMultipleStatements());
        // Un ";" dans une chaîne ou un commentaire n'est pas un séparateur
        $this->assertFalse((new SqlQueryInspector("SELECT ';x' -- ; DROP\nFROM t"))->hasMultipleStatements());
    }

    /**
     * Vérifie le repérage des mauvaises pratiques : SELECT *, fonction sur une colonne
     * dans le WHERE, LIKE commençant par %
     */
    public function testBadPracticesAreDetected(): void
    {
        $inspector = new SqlQueryInspector(
            "SELECT * FROM emprunts e WHERE YEAR(e.date_emprunt) = 2024 AND LOWER(nom) = 'x' AND e.titre LIKE '%chat%'"
        );

        $this->assertTrue($inspector->hasSelectStar());
        $this->assertSame(['YEAR(e.date_emprunt)', 'LOWER(nom)'], array_column($inspector->getConditions()->functionsOnWhereColumns(), 'expression'));
        $this->assertSame(['e.titre'], $inspector->getConditions()->leadingWildcardLikes());

        $clean = new SqlQueryInspector("SELECT COUNT(*) FROM livres WHERE titre LIKE 'Le%'");
        $this->assertFalse($clean->hasSelectStar());
        $this->assertSame([], $clean->getConditions()->functionsOnWhereColumns());
        $this->assertSame([], $clean->getConditions()->leadingWildcardLikes());
    }
}
