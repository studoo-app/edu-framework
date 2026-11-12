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
 * type de requête, tables et alias, colonnes utilisées dans les jointures,
 * les filtres (WHERE), les tris (ORDER BY) et les regroupements (GROUP BY),
 * paramètres (:nom ou ?) et mauvaises pratiques repérables dans le texte.
 *
 * Ce n'est pas un analyseur SQL complet : la lecture repose sur des
 * expressions régulières appliquées à la requête débarrassée de ses
 * commentaires et de ses chaînes de caractères. Elle couvre les requêtes
 * écrites en TP (SELECT, jointures, sous-requêtes simples) ; les conseils
 * qui en découlent restent des suggestions à vérifier.
 *
 * @package Studoo\EduFramework\Core\Profiler\Sql
 */
class SqlQueryInspector
{
    /**
     * Mots-clés qui ne peuvent pas être l'alias d'une table
     */
    private const array RESERVED = [
        'WHERE', 'ON', 'USING', 'JOIN', 'INNER', 'LEFT', 'RIGHT', 'FULL', 'OUTER', 'CROSS', 'NATURAL',
        'STRAIGHT_JOIN', 'GROUP', 'ORDER', 'HAVING', 'LIMIT', 'OFFSET', 'UNION', 'EXCEPT', 'INTERSECT',
        'WINDOW', 'SET', 'VALUES', 'SELECT', 'FOR', 'LOCK', 'INTO', 'AS', 'FORCE', 'IGNORE', 'USE',
        'PARTITION', 'RETURNING', 'AND', 'OR', 'NOT', 'WITH', 'FETCH',
    ];

    /**
     * Fonctions qui, appliquées à une colonne dans un WHERE, empêchent l'utilisation d'un index
     */
    private const array COLUMN_FUNCTIONS = [
        'YEAR', 'MONTH', 'DAY', 'DATE', 'HOUR', 'DATE_FORMAT', 'STRFTIME', 'LOWER', 'UPPER', 'LCASE',
        'UCASE', 'TRIM', 'LTRIM', 'RTRIM', 'SUBSTRING', 'SUBSTR', 'LEFT', 'RIGHT', 'CONCAT', 'IFNULL',
        'COALESCE', 'ABS', 'ROUND', 'FLOOR', 'CEIL', 'LENGTH', 'CHAR_LENGTH', 'CAST', 'CONVERT', 'REPLACE',
    ];

    /**
     * Mots-clés qui terminent une clause de la requête (au même niveau de parenthèses)
     */
    private const string CLAUSE_END = 'WHERE|GROUP\s+BY|HAVING|ORDER\s+BY|LIMIT|OFFSET|UNION|EXCEPT|INTERSECT|'
        . 'WINDOW|FOR\s+UPDATE|RETURNING|FETCH';

    /**
     * Requête d'origine
     */
    private string $sql;

    /**
     * Requête sans commentaires ni guillemets d'identifiants (les chaînes sont conservées)
     */
    private string $withoutComments;

    /**
     * Requête sans commentaires, les chaînes de caractères remplacées par ''
     */
    private string $clean;

    /**
     * Profondeur de parenthèses de chaque caractère de la requête nettoyée
     * @var array<int, int>
     */
    private array $depths = [];

    /**
     * SqlQueryInspector constructor.
     *
     * @param string $sql Requête SQL à analyser
     */
    public function __construct(string $sql)
    {
        $this->sql = $sql;
        $this->withoutComments = $this->stripComments($sql);
        $this->clean = (string) preg_replace("/'(?:[^'\\\\]|\\\\.|'')*'/s", "''", $this->withoutComments);
        $this->clean = (string) preg_replace('/\s+/', ' ', $this->clean);

        $depth = 0;
        $length = strlen($this->clean);
        for ($i = 0; $i < $length; $i++) {
            if ($this->clean[$i] === '(') {
                $depth++;
            }
            $this->depths[$i] = $depth;
            if ($this->clean[$i] === ')') {
                $depth = max(0, $depth - 1);
            }
        }
    }

    /**
     * Retourne la requête d'origine
     *
     * @return string
     */
    public function getSql(): string
    {
        return $this->sql;
    }

    /**
     * Retourne le premier mot-clé de la requête en majuscules (Exemple : SELECT, WITH, UPDATE)
     *
     * @return string
     */
    public function statementType(): string
    {
        if (preg_match('/^[\s(]*([A-Za-z]+)/', $this->clean, $match) !== 1) {
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
        $type = $this->statementType();
        if ($type === 'SELECT') {
            return preg_match('/\bINTO\s+(?:OUTFILE|DUMPFILE)\b|\bFOR\s+UPDATE\b/i', $this->clean) !== 1;
        }

        return $type === 'WITH'
            && preg_match('/\b(?:INSERT|UPDATE|DELETE|REPLACE|MERGE)\b|\bINTO\s+(?:OUTFILE|DUMPFILE)\b/i', $this->clean) !== 1;
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
        return preg_match('/;\s*\S/', $this->clean) === 1;
    }

    /**
     * Retourne les paramètres de la requête : noms (Exemple : ":id") dans l'ordre
     * d'apparition, sans doublon, ou positions (1, 2, ...) pour les paramètres "?"
     *
     * @return array<int, int|string>
     */
    public function placeholders(): array
    {
        $named = [];
        if (preg_match_all('/(?<![:\w]):([A-Za-z_]\w*)/', $this->clean, $matches) > 0) {
            foreach ($matches[1] as $name) {
                $named[':' . $name] = true;
            }
        }
        if ($named !== []) {
            return array_keys($named);
        }

        $count = substr_count($this->clean, '?');

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

        foreach ($this->clauseBodies('FROM', self::CLAUSE_END) as $body) {
            $flat = $this->dropNested($body);
            $pieces = preg_split(
                '/,|\b(?:NATURAL\s+)?(?:(?:LEFT|RIGHT|FULL)(?:\s+OUTER)?\s+|INNER\s+|CROSS\s+)?JOIN\b|\bSTRAIGHT_JOIN\b/i',
                $flat
            );
            foreach ($pieces === false ? [] : $pieces as $piece) {
                $piece = (string) preg_replace('/\b(?:ON|USING)\b.*$/is', '', $piece);
                if (preg_match('/^\s*([A-Za-z_][\w$]*(?:\.[A-Za-z_][\w$]*)?)(?:\s+(?:AS\s+)?([A-Za-z_]\w*))?/i', $piece, $match) !== 1) {
                    continue;
                }
                $this->addTable($tables, $match[1], $match[2] ?? '');
            }
        }

        // Tables modifiées : UPDATE t, INSERT INTO t, REPLACE INTO t, DELETE FROM t (déjà lue par FROM)
        if (preg_match_all('/\b(?:UPDATE|INTO)\s+([A-Za-z_][\w$]*(?:\.[A-Za-z_][\w$]*)?)(?:\s+(?:AS\s+)?([A-Za-z_]\w*))?/i', $this->clean, $matches, PREG_SET_ORDER) > 0) {
            foreach ($matches as $match) {
                $this->addTable($tables, $match[1], $match[2] ?? '');
            }
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
     * Retourne les conditions d'égalité entre colonnes de deux tables
     * (ON a.x = b.y, ou jointure écrite dans le WHERE)
     *
     * @return array<int, array{left_alias: string, left_column: string, right_alias: string, right_column: string}>
     */
    public function joinConditions(): array
    {
        $joins = [];
        $bodies = array_merge(
            $this->clauseBodies('ON', 'JOIN|LEFT|RIGHT|INNER|CROSS|FULL|NATURAL|STRAIGHT_JOIN|' . self::CLAUSE_END),
            $this->clauseBodies('WHERE', self::CLAUSE_END)
        );
        foreach ($bodies as $body) {
            if (preg_match_all('/\b([A-Za-z_]\w*)\.([A-Za-z_]\w*)\s*=\s*([A-Za-z_]\w*)\.([A-Za-z_]\w*)\b(?!\s*\()/', $body, $matches, PREG_SET_ORDER) > 0) {
                foreach ($matches as $match) {
                    $joins[] = [
                        'left_alias' => strtolower($match[1]),
                        'left_column' => $match[2],
                        'right_alias' => strtolower($match[3]),
                        'right_column' => $match[4],
                    ];
                }
            }
        }

        return $joins;
    }

    /**
     * Retourne les colonnes filtrées dans les clauses WHERE (comparées à une valeur :
     * =, <, >, IN, LIKE, BETWEEN, IS), avec leur alias (null si la colonne n'est pas préfixée)
     * Les conditions de jointure (a.x = b.y) ne sont pas retournées
     *
     * @return array<int, array{alias: string|null, column: string, operator: string}>
     */
    public function whereColumns(): array
    {
        $columns = [];
        foreach ($this->clauseBodies('WHERE', self::CLAUSE_END) as $body) {
            $flat = $this->dropNested($body);
            $pattern = '/(?<![\w.:])(?:([A-Za-z_]\w*)\.)?([A-Za-z_]\w*)\s*(=|<=>|<>|!=|<=|>=|<|>|\bNOT\s+IN\b|\bIN\b|\bNOT\s+LIKE\b|\bLIKE\b|\bBETWEEN\b|\bIS\b)\s*((?:[A-Za-z_]\w*\.[A-Za-z_]\w*)?)/i';
            if (preg_match_all($pattern, $flat, $matches, PREG_SET_ORDER) === 0) {
                continue;
            }
            foreach ($matches as $match) {
                if (in_array(strtoupper($match[2]), self::RESERVED, true) === true
                    || in_array(strtoupper($match[2]), ['NULL', 'TRUE', 'FALSE'], true) === true
                    || ($match[4] !== '' && $match[3] === '=')) {
                    continue;
                }
                $columns[] = [
                    'alias' => ($match[1] === '' ? null : strtolower($match[1])),
                    'column' => $match[2],
                    'operator' => strtoupper((string) preg_replace('/\s+/', ' ', $match[3])),
                ];
            }
        }

        return $columns;
    }

    /**
     * Retourne les éléments du ORDER BY de la requête principale
     *
     * @return array<int, array{alias: string|null, column: string|null, expression: string, direction: string}>
     */
    public function orderByItems(): array
    {
        return $this->listItems('ORDER\s+BY', 'LIMIT|OFFSET|FETCH|FOR\s+UPDATE|WINDOW|UNION');
    }

    /**
     * Retourne les éléments du GROUP BY de la requête principale
     *
     * @return array<int, array{alias: string|null, column: string|null, expression: string, direction: string}>
     */
    public function groupByItems(): array
    {
        return $this->listItems('GROUP\s+BY', 'HAVING|ORDER\s+BY|LIMIT|OFFSET|WINDOW|UNION|WITH\s+ROLLUP');
    }

    /**
     * Retourne les alias de colonnes déclarés dans le SELECT principal (Exemple : COUNT(*) AS total)
     *
     * @return array<int, string>
     */
    public function selectAliases(): array
    {
        $aliases = [];
        foreach ($this->clauseBodies('SELECT', 'FROM', true) as $body) {
            if (preg_match_all('/\bAS\s+([A-Za-z_]\w*)/i', $body, $matches) > 0) {
                foreach ($matches[1] as $alias) {
                    $aliases[] = strtolower($alias);
                }
            }
        }

        return array_values(array_unique($aliases));
    }

    /**
     * Indique si la requête sélectionne toutes les colonnes (SELECT * ou SELECT t.*)
     *
     * @return bool
     */
    public function hasSelectStar(): bool
    {
        return preg_match('/\bSELECT\s+(?:DISTINCT\s+)?(?:[A-Za-z_]\w*\.)?\*/i', $this->clean) === 1
            || preg_match('/\bSELECT\s[^()]*?,\s*(?:[A-Za-z_]\w*\.)?\*\s*(?:,|\bFROM\b)/i', $this->clean) === 1;
    }

    /**
     * Indique si la requête principale a une clause LIMIT (ou FETCH FIRST)
     *
     * @return bool
     */
    public function hasLimit(): bool
    {
        return $this->clauseBodies('LIMIT|FETCH', '$^', true) !== [];
    }

    /**
     * Retourne les fonctions appliquées à une colonne dans les clauses WHERE
     * (Exemple : "YEAR(e.date_emprunt)") : l'index de la colonne ne peut pas être utilisé
     *
     * @return array<int, array{expression: string, alias: string|null, column: string, function: string}>
     */
    public function functionsOnWhereColumns(): array
    {
        /** @var array<string, array{expression: string, alias: string|null, column: string, function: string}> $found */
        $found = [];
        $functions = implode('|', self::COLUMN_FUNCTIONS);
        foreach ($this->clauseBodies('WHERE', self::CLAUSE_END) as $body) {
            $pattern = '/\b(' . $functions . ')\s*\(\s*(?:([A-Za-z_]\w*)\.)?([A-Za-z_]\w*)\s*(?:[,)]|\bAS\b)/i';
            if (preg_match_all($pattern, $body, $matches, PREG_SET_ORDER) === 0) {
                continue;
            }
            foreach ($matches as $match) {
                if (in_array(strtoupper($match[3]), ['NULL', 'SELECT'], true) === true) {
                    continue;
                }
                $expression = strtoupper($match[1]) . '(' . ($match[2] === '' ? '' : $match[2] . '.') . $match[3] . ')';
                $found[$expression] = [
                    'expression' => $expression,
                    'alias' => ($match[2] === '' ? null : strtolower($match[2])),
                    'column' => $match[3],
                    'function' => strtoupper($match[1]),
                ];
            }
        }

        return array_values($found);
    }

    /**
     * Retourne les conditions LIKE dont le motif commence par % (Exemple : LIKE '%dupont')
     * : un index ne peut pas être utilisé pour les trouver
     *
     * @return array<int, string>
     */
    public function leadingWildcardLikes(): array
    {
        $found = [];
        $pattern = "/((?:[A-Za-z_]\\w*\\.)?[A-Za-z_]\\w*)\\s+LIKE\\s+(?:CONCAT\\s*\\(\\s*)?'%/i";
        $text = (string) preg_replace('/\s+/', ' ', $this->withoutComments);
        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER) > 0) {
            foreach ($matches as $match) {
                $found[$match[1]] = $match[1];
            }
        }

        return array_values($found);
    }

    /**
     * Retourne la requête sur une ligne, sans commentaires et les chaînes remplacées par ''
     *
     * @return string
     */
    public function cleanSql(): string
    {
        return $this->clean;
    }

    /**
     * Supprime les commentaires (-- ..., # ..., /* ... *\/) et les guillemets
     * d'identifiants (`table`) en conservant les chaînes de caractères
     *
     * @param string $sql Requête SQL
     * @return string
     */
    private function stripComments(string $sql): string
    {
        $result = '';
        $length = strlen($sql);
        $i = 0;
        while ($i < $length) {
            $char = $sql[$i];
            $next = ($i + 1 < $length ? $sql[$i + 1] : '');
            if ($char === "'") {
                // Chaîne de caractères conservée telle quelle (échappements \' et '')
                $end = $i + 1;
                while ($end < $length) {
                    if ($sql[$end] === '\\') {
                        $end += 2;
                        continue;
                    }
                    if ($sql[$end] === "'") {
                        if ($end + 1 < $length && $sql[$end + 1] === "'") {
                            $end += 2;
                            continue;
                        }
                        break;
                    }
                    $end++;
                }
                $result .= substr($sql, $i, $end - $i + 1);
                $i = $end + 1;
            } elseif (($char === '-' && $next === '-') || $char === '#') {
                $end = strpos($sql, "\n", $i);
                $i = ($end === false ? $length : $end);
                $result .= ' ';
            } elseif ($char === '/' && $next === '*') {
                $end = strpos($sql, '*/', $i + 2);
                $i = ($end === false ? $length : $end + 2);
                $result .= ' ';
            } elseif ($char === '`') {
                $i++;
            } else {
                $result .= $char;
                $i++;
            }
        }

        return $result;
    }

    /**
     * Retourne le contenu de chaque clause ouverte par un mot-clé : du mot-clé jusqu'au
     * prochain mot-clé de fin au même niveau de parenthèses, ou jusqu'à la parenthèse
     * fermante qui termine la sous-requête
     *
     * @param string $keyword Expression régulière du mot-clé d'ouverture (Exemple : WHERE)
     * @param string $terminators Expression régulière des mots-clés de fin
     * @param bool $topLevelOnly Ne retourner que les clauses de la requête principale
     * @return array<int, string>
     */
    private function clauseBodies(string $keyword, string $terminators, bool $topLevelOnly = false): array
    {
        $bodies = [];
        if (preg_match_all('/\b(?:' . $keyword . ')\b/i', $this->clean, $matches, PREG_OFFSET_CAPTURE) === 0) {
            return $bodies;
        }

        $length = strlen($this->clean);
        foreach ($matches[0] as [$text, $offset]) {
            $depth = $this->depths[$offset] ?? 0;
            if ($topLevelOnly === true && $depth !== $this->topDepth()) {
                continue;
            }
            $start = $offset + strlen($text);
            $end = $length;
            for ($i = $start; $i < $length; $i++) {
                if ($this->clean[$i] === ')' && ($this->depths[$i] ?? 0) <= $depth) {
                    $end = $i;
                    break;
                }
                if (($this->depths[$i] ?? 0) === $depth && ctype_alpha($this->clean[$i]) === true
                    && ($i === 0 || preg_match('/\w/', $this->clean[$i - 1]) !== 1)
                    && preg_match('/\G(?:' . $terminators . ')\b/i', $this->clean, $unused, 0, $i) === 1) {
                    $end = $i;
                    break;
                }
            }
            $bodies[] = trim(substr($this->clean, $start, $end - $start));
        }

        return $bodies;
    }

    /**
     * Retourne la profondeur de parenthèses de la requête principale
     * (0, ou 1 si toute la requête est entre parenthèses)
     *
     * @return int
     */
    private function topDepth(): int
    {
        return (preg_match('/^\s*\(/', $this->clean) === 1 ? 1 : 0);
    }

    /**
     * Retire le contenu des parenthèses imbriquées d'un fragment de requête
     * (sous-requêtes, arguments de fonctions) : "a IN (SELECT ...)" devient "a IN ()"
     *
     * @param string $fragment Fragment de requête
     * @return string
     */
    private function dropNested(string $fragment): string
    {
        $result = '';
        $depth = 0;
        $length = strlen($fragment);
        for ($i = 0; $i < $length; $i++) {
            $char = $fragment[$i];
            if ($char === '(') {
                if ($depth === 0) {
                    $result .= '(';
                }
                $depth++;
                continue;
            }
            if ($char === ')') {
                $depth = max(0, $depth - 1);
                if ($depth === 0) {
                    $result .= ')';
                }
                continue;
            }
            if ($depth === 0) {
                $result .= $char;
            }
        }

        return $result;
    }

    /**
     * Découpe une liste de la requête principale (ORDER BY, GROUP BY) en éléments
     *
     * @param string $keyword Expression régulière du mot-clé d'ouverture
     * @param string $terminators Expression régulière des mots-clés de fin
     * @return array<int, array{alias: string|null, column: string|null, expression: string, direction: string}>
     */
    private function listItems(string $keyword, string $terminators): array
    {
        $bodies = $this->clauseBodies($keyword, $terminators, true);
        if ($bodies === []) {
            return [];
        }

        $items = [];
        $aliases = $this->selectAliases();
        foreach ($this->splitTopLevel(end($bodies)) as $item) {
            $direction = 'ASC';
            if (preg_match('/\s+(ASC|DESC)\s*$/i', $item, $match) === 1) {
                $direction = strtoupper($match[1]);
                $item = trim(substr($item, 0, -strlen($match[0])));
            }
            $item = trim((string) preg_replace('/\s+NULLS\s+(?:FIRST|LAST)\s*$/i', '', $item));
            $alias = null;
            $column = null;
            if (preg_match('/^(?:([A-Za-z_]\w*)\.)?([A-Za-z_]\w*)$/', $item, $match) === 1
                && ($match[1] !== '' || in_array(strtolower($match[2]), $aliases, true) === false)) {
                $alias = ($match[1] === '' ? null : strtolower($match[1]));
                $column = $match[2];
            }
            $items[] = ['alias' => $alias, 'column' => $column, 'expression' => $item, 'direction' => $direction];
        }

        return $items;
    }

    /**
     * Découpe un fragment de requête sur les virgules qui ne sont pas entre parenthèses
     *
     * @param string $fragment Fragment de requête
     * @return array<int, string>
     */
    private function splitTopLevel(string $fragment): array
    {
        $parts = [];
        $current = '';
        $depth = 0;
        $length = strlen($fragment);
        for ($i = 0; $i < $length; $i++) {
            $char = $fragment[$i];
            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth = max(0, $depth - 1);
            } elseif ($char === ',' && $depth === 0) {
                $parts[] = trim($current);
                $current = '';
                continue;
            }
            $current .= $char;
        }
        if (trim($current) !== '') {
            $parts[] = trim($current);
        }

        return $parts;
    }

    /**
     * Ajoute une table (et son alias) à la liste des tables de la requête
     *
     * @param array<string, string> $tables Tables de la requête (alias => table)
     * @param string $name Nom de la table, éventuellement préfixé par la base (Exemple : app_db.livres)
     * @param string $alias Alias de la table (vide si aucun)
     * @return void
     */
    private function addTable(array &$tables, string $name, string $alias): void
    {
        $parts = explode('.', $name);
        $table = end($parts);
        if (in_array(strtoupper($table), self::RESERVED, true) === true || in_array(strtoupper($table), ['DUAL', 'LATERAL'], true) === true) {
            return;
        }

        $tables[strtolower($table)] = $table;
        if ($alias !== '' && in_array(strtoupper($alias), self::RESERVED, true) === false) {
            $tables[strtolower($alias)] = $table;
        }
    }
}
