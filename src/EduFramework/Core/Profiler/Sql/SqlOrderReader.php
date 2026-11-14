<?php

/*
 * Edu Framework by studoo labs
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Profiler\Sql;

/**
 * Class SqlOrderReader
 * Lecture des tris et regroupements de la requête principale : éléments du
 * ORDER BY et du GROUP BY (colonne ou valeur calculée, sens du tri) et alias
 * de colonnes déclarés dans le SELECT.
 *
 * @package Studoo\EduFramework\Core\Profiler\Sql
 */
class SqlOrderReader
{
    /**
     * SqlOrderReader constructor.
     *
     * @param SqlText $text Texte de la requête
     */
    public function __construct(private SqlText $text)
    {
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
     * Retourne les valeurs calculées du ORDER BY (expressions et alias du SELECT,
     * Exemple : total_emprunts) : aucun index ne peut éviter leur tri
     *
     * @return array<int, string>
     */
    public function computedOrderBy(): array
    {
        $computed = array_filter($this->orderByItems(), static fn (array $item): bool => $item['column'] === null);

        return array_values(array_map(static fn (array $item): string => $item['expression'], $computed));
    }

    /**
     * Retourne les alias de colonnes déclarés dans le SELECT principal (Exemple : COUNT(*) AS total)
     *
     * @return array<int, string>
     */
    public function selectAliases(): array
    {
        $aliases = [];
        foreach ($this->text->clauseBodies('SELECT', 'FROM', true) as $body) {
            preg_match_all('/\bAS\s+([A-Za-z_]\w*)/i', $body, $matches);
            $aliases = array_merge($aliases, array_map('strtolower', $matches[1]));
        }

        return array_values(array_unique($aliases));
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
        $bodies = $this->text->clauseBodies($keyword, $terminators, true);
        if ($bodies === []) {
            return [];
        }

        $aliases = $this->selectAliases();

        return array_map(
            static fn (string $item): array => self::parseItem($item, $aliases),
            SqlText::splitTopLevel((string) end($bodies))
        );
    }

    /**
     * Lit un élément de ORDER BY / GROUP BY : colonne (avec son alias de table) ou
     * valeur calculée (expression, ou alias déclaré dans le SELECT), et sens du tri
     *
     * @param string $item Élément (Exemple : "u.nom DESC")
     * @param array<int, string> $selectAliases Alias de colonnes du SELECT (minuscules)
     * @return array{alias: string|null, column: string|null, expression: string, direction: string}
     */
    private static function parseItem(string $item, array $selectAliases): array
    {
        $direction = 'ASC';
        if (preg_match('/\s+(ASC|DESC)\s*$/i', $item, $match) === 1) {
            $direction = strtoupper($match[1]);
            $item = trim(substr($item, 0, -strlen($match[0])));
        }
        $item = trim((string) preg_replace('/\s+NULLS\s+(?:FIRST|LAST)\s*$/i', '', $item));

        $parsed = ['alias' => null, 'column' => null, 'expression' => $item, 'direction' => $direction];
        $isColumn = preg_match('/^(?:([A-Za-z_]\w*)\.)?([A-Za-z_]\w*)$/', $item, $column) === 1;
        if ($isColumn === true && ($column[1] !== '' || in_array(strtolower($column[2]), $selectAliases, true) === false)) {
            $parsed['alias'] = ($column[1] === '' ? null : strtolower($column[1]));
            $parsed['column'] = $column[2];
        }

        return $parsed;
    }
}
