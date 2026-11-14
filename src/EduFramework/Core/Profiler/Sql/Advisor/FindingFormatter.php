<?php

/*
 * Edu Framework by studoo labs
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Profiler\Sql\Advisor;

/**
 * Class FindingFormatter
 * Construction des constats de l'analyse EXPLAIN et des libellés qu'ils
 * affichent (table et alias, index, colonnes, nombre de lignes).
 *
 * @package Studoo\EduFramework\Core\Profiler\Sql\Advisor
 */
class FindingFormatter
{
    /**
     * Construit un constat
     *
     * @param string $level Niveau (critical, warning, info, success)
     * @param string $title Titre
     * @param string $detail Explication
     * @param string|null $suggestion Suggestion SQL
     * @param string|null $table Table concernée
     * @return array{level: string, title: string, detail: string, suggestion: string|null, table: string|null}
     */
    public static function finding(string $level, string $title, string $detail, ?string $suggestion = null, ?string $table = null): array
    {
        return ['level' => $level, 'title' => $title, 'detail' => $detail, 'suggestion' => $suggestion, 'table' => $table];
    }

    /**
     * Libellé d'une table : « emprunts » ou « emprunts » (alias e)
     *
     * @param string $table Nom de la table
     * @param string $alias Alias de la table (minuscules)
     * @return string
     */
    public static function tableLabel(string $table, string $alias): string
    {
        return '« ' . $table . ' »' . ($alias !== '' && $alias !== strtolower($table) ? ' (alias ' . $alias . ')' : '');
    }

    /**
     * Libellé de l'index utilisé par un accès
     *
     * @param array<string, mixed> $access Accès normalisé
     * @return string
     */
    public static function keyLabel(array $access): string
    {
        return (isset($access['key']) === true && $access['key'] !== '' ? (string) $access['key'] : 'index');
    }

    /**
     * Libellé de colonnes préfixées par l'alias de la table : e.utilisateur_id, e.livre_id
     *
     * @param string $alias Alias de la table (minuscules)
     * @param array<int, string> $columns Colonnes
     * @param bool $singleTable La requête ne porte que sur une table (pas de préfixe)
     * @return string
     */
    public static function columnsLabel(string $alias, array $columns, bool $singleTable): string
    {
        $prefix = ($alias === '' || $singleTable === true ? '' : $alias . '.');

        return implode(', ', array_map(static fn (string $column): string => $prefix . $column, $columns));
    }

    /**
     * Libellé du nombre de lignes : " (≈ 4 000 lignes)", vide si inconnu
     *
     * @param int|null $rows Nombre de lignes
     * @return string
     */
    public static function rowsLabel(?int $rows): string
    {
        return ($rows === null ? '' : ' (≈ ' . self::formatNumber($rows) . ' ligne' . ($rows > 1 ? 's' : '') . ')');
    }

    /**
     * Formate un nombre entier à la française (Exemple : 4 000)
     *
     * @param int $number Nombre
     * @return string
     */
    public static function formatNumber(int $number): string
    {
        return number_format($number, 0, ',', "\u{202F}");
    }
}
