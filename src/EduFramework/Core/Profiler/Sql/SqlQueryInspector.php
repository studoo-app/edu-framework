<?php

/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Profiler\Sql;

/**
 * Class SqlQueryInspector
 * Lecture du texte d'une requête SQL pour l'analyse EXPLAIN du profiler :
 * type de requête, contrôles de sécurité, paramètres (:nom ou ?) et tables.
 * Les conditions (jointures, WHERE) sont lues par SqlConditionReader et les
 * tris et regroupements (ORDER BY, GROUP BY) par SqlOrderReader.
 *
 * Ce n'est pas un analyseur SQL complet : la lecture repose sur des
 * expressions régulières appliquées à la requête débarrassée de ses
 * commentaires et de ses chaînes de caractères (SqlText). Elle couvre les
 * requêtes écrites en TP (SELECT, jointures, sous-requêtes simples) ; les
 * conseils qui en découlent restent des suggestions à vérifier.
 *
 * @package Studoo\EduFramework\Core\Profiler\Sql
 */
class SqlQueryInspector
{
    /**
     * Mots-clés qui ne peuvent pas être un nom ou un alias de table
     */
    public const array RESERVED = [
        'WHERE', 'ON', 'USING', 'JOIN', 'INNER', 'LEFT', 'RIGHT', 'FULL', 'OUTER', 'CROSS', 'NATURAL',
        'STRAIGHT_JOIN', 'GROUP', 'ORDER', 'HAVING', 'LIMIT', 'OFFSET', 'UNION', 'EXCEPT', 'INTERSECT',
        'WINDOW', 'SET', 'VALUES', 'SELECT', 'FOR', 'LOCK', 'INTO', 'AS', 'FORCE', 'IGNORE', 'USE',
        'PARTITION', 'RETURNING', 'AND', 'OR', 'NOT', 'WITH', 'FETCH', 'DUAL', 'LATERAL',
    ];

    /**
     * Mots-clés qui terminent une clause de la requête (au même niveau de parenthèses)
     */
    public const string CLAUSE_END = 'WHERE|GROUP\s+BY|HAVING|ORDER\s+BY|LIMIT|OFFSET|UNION|EXCEPT|INTERSECT|'
        . 'WINDOW|FOR\s+UPDATE|RETURNING|FETCH';

    /**
     * Séparateurs des tables d'un FROM : virgule ou jointure
     */
    private const string TABLE_SEPARATORS = '/,|\b(?:NATURAL\s+)?(?:(?:LEFT|RIGHT|FULL)(?:\s+OUTER)?\s+|INNER\s+|CROSS\s+)?JOIN\b'
        . '|\bSTRAIGHT_JOIN\b/i';

    /**
     * Nom de table (éventuellement préfixé par la base) suivi de son alias
     */
    private const string TABLE_NAME = '([A-Za-z_][\w$]*(?:\.[A-Za-z_][\w$]*)?)(?:\s+(?:AS\s+)?([A-Za-z_]\w*))?';

    /**
     * Texte de la requête
     */
    private SqlText $text;

    /**
     * SqlQueryInspector constructor.
     *
     * @param string $sql Requête SQL à analyser
     */
    public function __construct(string $sql)
    {
        $this->text = new SqlText($sql);
    }

    /**
     * Retourne la requête d'origine
     *
     * @return string
     */
    public function getSql(): string
    {
        return $this->text->getSql();
    }

    /**
     * Retourne le texte préparé de la requête
     *
     * @return SqlText
     */
    public function getText(): SqlText
    {
        return $this->text;
    }

    /**
     * Retourne la lecture des conditions de la requête (jointures, WHERE)
     *
     * @return SqlConditionReader
     */
    public function getConditions(): SqlConditionReader
    {
        return new SqlConditionReader($this->text);
    }

    /**
     * Retourne la lecture des tris et regroupements de la requête (ORDER BY, GROUP BY)
     *
     * @return SqlOrderReader
     */
    public function getOrdering(): SqlOrderReader
    {
        return new SqlOrderReader($this->text);
    }

    /**
     * Retourne le premier mot-clé de la requête en majuscules (Exemple : SELECT, WITH, UPDATE)
     *
     * @return string
     */
    public function statementType(): string
    {
        if (preg_match('/^[\s(]*([A-Za-z]+)/', $this->text->getClean(), $match) !== 1) {
            return '';
        }

        return strtoupper($match[1]);
    }

    /**
     * Indique si la requête ne fait que lire des données (SELECT, ou WITH ... SELECT
     * sans INSERT / UPDATE / DELETE) : seule une telle requête peut être réellement
     * exécutée par EXPLAIN ANALYZE
     *
     * @return bool
     */
    public function isReadOnly(): bool
    {
        $writes = [
            'SELECT' => '/\bINTO\s+(?:OUTFILE|DUMPFILE)\b|\bFOR\s+UPDATE\b/i',
            'WITH' => '/\b(?:INSERT|UPDATE|DELETE|REPLACE|MERGE)\b|\bINTO\s+(?:OUTFILE|DUMPFILE)\b/i',
        ];
        $pattern = $writes[$this->statementType()] ?? null;

        return $pattern !== null && preg_match($pattern, $this->text->getClean()) !== 1;
    }

    /**
     * Indique si la requête peut être analysée par EXPLAIN (sans être exécutée) :
     * SELECT, WITH, INSERT, UPDATE, DELETE ou REPLACE
     *
     * @return bool
     */
    public function isExplainable(): bool
    {
        return in_array($this->statementType(), ['SELECT', 'WITH', 'INSERT', 'UPDATE', 'DELETE', 'REPLACE'], true);
    }

    /**
     * Indique si le texte contient plusieurs requêtes séparées par un point-virgule
     * (refusées par l'analyse : seule la première serait expliquée, les suivantes exécutées)
     *
     * @return bool
     */
    public function hasMultipleStatements(): bool
    {
        return preg_match('/;\s*\S/', $this->text->getClean()) === 1;
    }

    /**
     * Indique si la requête sélectionne toutes les colonnes (SELECT * ou SELECT t.*)
     *
     * @return bool
     */
    public function hasSelectStar(): bool
    {
        return preg_match('/\bSELECT\s+(?:DISTINCT\s+)?(?:[A-Za-z_]\w*\.)?\*/i', $this->text->getClean()) === 1
            || preg_match('/\bSELECT\s[^()]*?,\s*(?:[A-Za-z_]\w*\.)?\*\s*(?:,|\bFROM\b)/i', $this->text->getClean()) === 1;
    }

    /**
     * Indique si la requête principale a une clause LIMIT (ou FETCH FIRST)
     *
     * @return bool
     */
    public function hasLimit(): bool
    {
        return $this->text->clauseBodies('LIMIT|FETCH', '$^', true) !== [];
    }

    /**
     * Retourne les paramètres de la requête : noms (Exemple : ":id") dans l'ordre
     * d'apparition, sans doublon, ou positions (1, 2, ...) pour les paramètres "?"
     *
     * @return array<int, int|string>
     */
    public function placeholders(): array
    {
        preg_match_all('/(?<![:\w]):([A-Za-z_]\w*)/', $this->text->getClean(), $matches);
        if ($matches[1] !== []) {
            return array_values(array_unique(array_map(static fn (string $name): string => ':' . $name, $matches[1])));
        }

        $count = substr_count($this->text->getClean(), '?');

        return ($count === 0 ? [] : range(1, $count));
    }

    /**
     * Retourne les tables de la requête, indexées par alias en minuscules
     * (une table sans alias est indexée par son nom) : alias => nom de la table
     *
     * @return array<string, string>
     */
    public function tables(): array
    {
        $tables = [];
        foreach ($this->text->clauseBodies('FROM', self::CLAUSE_END) as $body) {
            foreach (preg_split(self::TABLE_SEPARATORS, SqlText::dropNested($body)) ?: [] as $piece) {
                $piece = (string) preg_replace('/\b(?:ON|USING)\b.*$/is', '', $piece);
                if (preg_match('/^\s*' . self::TABLE_NAME . '/i', $piece, $match) === 1) {
                    $tables += self::tableEntries($match[1], $match[2] ?? '');
                }
            }
        }

        // Tables modifiées : UPDATE t, INSERT INTO t, REPLACE INTO t (DELETE FROM t est lue par FROM)
        preg_match_all('/\b(?:UPDATE|INTO)\s+' . self::TABLE_NAME . '/i', $this->text->getClean(), $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $tables += self::tableEntries($match[1], $match[2] ?? '');
        }

        return $tables;
    }

    /**
     * Retourne les noms des tables de la requête, sans doublon
     *
     * @return array<int, string>
     */
    public function tableNames(): array
    {
        return array_values(array_unique(array_values($this->tables())));
    }

    /**
     * Retourne les entrées d'une table pour la liste des tables : son nom et son alias
     * (aucune entrée pour un mot-clé)
     *
     * @param string $name Nom de la table, éventuellement préfixé par la base (Exemple : app_db.livres)
     * @param string $alias Alias de la table (vide si aucun)
     * @return array<string, string>
     */
    private static function tableEntries(string $name, string $alias): array
    {
        $parts = explode('.', $name);
        $table = (string) end($parts);
        if (in_array(strtoupper($table), self::RESERVED, true) === true) {
            return [];
        }

        $entries = [strtolower($table) => $table];
        if ($alias !== '' && in_array(strtoupper($alias), self::RESERVED, true) === false) {
            $entries[strtolower($alias)] = $table;
        }

        return $entries;
    }
}
