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
 * Class SqlConditionReader
 * Lecture des conditions d'une requête SQL : colonnes utilisées dans les
 * jointures et dans les filtres WHERE, et mauvaises pratiques qui empêchent
 * l'utilisation d'un index (fonction appliquée à une colonne, LIKE '%…').
 *
 * @package Studoo\EduFramework\Core\Profiler\Sql
 */
class SqlConditionReader
{
    /**
     * Fonctions qui, appliquées à une colonne dans un WHERE, empêchent l'utilisation d'un index
     */
    private const array COLUMN_FUNCTIONS = [
        'YEAR', 'MONTH', 'DAY', 'DATE', 'HOUR', 'DATE_FORMAT', 'STRFTIME', 'LOWER', 'UPPER', 'LCASE',
        'UCASE', 'TRIM', 'LTRIM', 'RTRIM', 'SUBSTRING', 'SUBSTR', 'LEFT', 'RIGHT', 'CONCAT', 'IFNULL',
        'COALESCE', 'ABS', 'ROUND', 'FLOOR', 'CEIL', 'LENGTH', 'CHAR_LENGTH', 'CAST', 'CONVERT', 'REPLACE',
    ];

    /**
     * Colonne (éventuellement préfixée par son alias) comparée à une valeur, suivie
     * de l'éventuelle colonne de l'autre côté de la comparaison
     */
    private const string FILTER = '/(?<![\w.:])(?:([A-Za-z_]\w*)\.)?([A-Za-z_]\w*)\s*(=|<=>|<>|!=|<=|>=|<|>|\bNOT\s+IN\b|\bIN\b'
        . '|\bNOT\s+LIKE\b|\bLIKE\b|\bBETWEEN\b|\bIS\b)\s*((?:[A-Za-z_]\w*\.[A-Za-z_]\w*)?)/i';

    /**
     * Égalité entre colonnes de deux tables : a.x = b.y
     */
    private const string JOIN = '/\b([A-Za-z_]\w*)\.([A-Za-z_]\w*)\s*=\s*([A-Za-z_]\w*)\.([A-Za-z_]\w*)\b(?!\s*\()/';

    /**
     * SqlConditionReader constructor.
     *
     * @param SqlText $text Texte de la requête
     */
    public function __construct(private SqlText $text)
    {
    }

    /**
     * Retourne les conditions d'égalité entre colonnes de deux tables
     * (ON a.x = b.y, ou jointure écrite dans le WHERE)
     *
     * @return array<int, array{left_alias: string, left_column: string, right_alias: string, right_column: string}>
     */
    public function joinConditions(): array
    {
        $bodies = array_merge(
            $this->text->clauseBodies('ON', 'JOIN|LEFT|RIGHT|INNER|CROSS|FULL|NATURAL|STRAIGHT_JOIN|' . SqlQueryInspector::CLAUSE_END),
            $this->whereBodies()
        );

        $joins = [];
        foreach ($bodies as $body) {
            preg_match_all(self::JOIN, $body, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $joins[] = [
                    'left_alias' => strtolower($match[1]),
                    'left_column' => $match[2],
                    'right_alias' => strtolower($match[3]),
                    'right_column' => $match[4],
                ];
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
        foreach ($this->whereBodies() as $body) {
            preg_match_all(self::FILTER, SqlText::dropNested($body), $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                if (self::isFilter($match) === true) {
                    $columns[] = [
                        'alias' => ($match[1] === '' ? null : strtolower($match[1])),
                        'column' => $match[2],
                        'operator' => strtoupper((string) preg_replace('/\s+/', ' ', $match[3])),
                    ];
                }
            }
        }

        return $columns;
    }

    /**
     * Retourne les fonctions appliquées à une colonne dans les clauses WHERE
     * (Exemple : "YEAR(e.date_emprunt)") : l'index de la colonne ne peut pas être utilisé
     *
     * @return array<int, array{expression: string, alias: string|null, column: string, function: string}>
     */
    public function functionsOnWhereColumns(): array
    {
        $pattern = '/\b(' . implode('|', self::COLUMN_FUNCTIONS) . ')\s*\(\s*(?:([A-Za-z_]\w*)\.)?([A-Za-z_]\w*)\s*(?:[,)]|\bAS\b)/i';

        /** @var array<string, array{expression: string, alias: string|null, column: string, function: string}> $found */
        $found = [];
        foreach ($this->whereBodies() as $body) {
            preg_match_all($pattern, $body, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                if (in_array(strtoupper($match[3]), ['NULL', 'SELECT'], true) === true) {
                    continue;
                }
                $function = strtoupper($match[1]);
                $expression = $function . '(' . ($match[2] === '' ? '' : $match[2] . '.') . $match[3] . ')';
                $found[$expression] = [
                    'expression' => $expression,
                    'alias' => ($match[2] === '' ? null : strtolower($match[2])),
                    'column' => $match[3],
                    'function' => $function,
                ];
            }
        }

        return array_values($found);
    }

    /**
     * Retourne les colonnes comparées par LIKE à un motif qui commence par %
     * (Exemple : LIKE '%dupont') : un index ne peut pas être utilisé pour les trouver
     *
     * @return array<int, string>
     */
    public function leadingWildcardLikes(): array
    {
        $pattern = "/((?:[A-Za-z_]\\w*\\.)?[A-Za-z_]\\w*)\\s+LIKE\\s+(?:CONCAT\\s*\\(\\s*)?'%/i";
        preg_match_all($pattern, $this->text->getWithoutComments(), $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * Retourne les noms des colonnes (minuscules) qu'un index ne peut pas servir :
     * colonnes enveloppées dans une fonction ou comparées à LIKE '%…'
     *
     * @return array<int, string>
     */
    public function unindexableColumns(): array
    {
        $columns = array_column($this->functionsOnWhereColumns(), 'column');
        foreach ($this->leadingWildcardLikes() as $column) {
            $parts = explode('.', $column);
            $columns[] = (string) end($parts);
        }

        return array_map('strtolower', $columns);
    }

    /**
     * Retourne le contenu des clauses WHERE de la requête et de ses sous-requêtes
     *
     * @return array<int, string>
     */
    private function whereBodies(): array
    {
        return $this->text->clauseBodies('WHERE', SqlQueryInspector::CLAUSE_END);
    }

    /**
     * Indique si une comparaison lue dans un WHERE filtre une colonne par une valeur :
     * ni mot-clé, ni constante, ni égalité entre deux colonnes (jointure)
     *
     * @param array<int, string> $match Comparaison lue (alias, colonne, opérateur, colonne comparée)
     * @return bool
     */
    private static function isFilter(array $match): bool
    {
        $column = strtoupper($match[2]);

        return in_array($column, SqlQueryInspector::RESERVED, true) === false
            && in_array($column, ['NULL', 'TRUE', 'FALSE'], true) === false
            && ($match[4] === '' || $match[3] !== '=');
    }
}
