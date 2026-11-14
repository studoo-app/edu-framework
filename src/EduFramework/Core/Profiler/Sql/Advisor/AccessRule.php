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

use Studoo\EduFramework\Core\Profiler\Sql\SqlExplainAdvisor;

/**
 * Class AccessRule
 * Constats sur l'accès à une table (une ligne du plan) : sous-requête corrélée,
 * index temporaire SQLite, parcours complet de la table (FullScanRule) ou de
 * l'index, et accès efficaces par index (bons points).
 *
 * @package Studoo\EduFramework\Core\Profiler\Sql\Advisor
 */
class AccessRule
{
    /**
     * Constats des parcours complets de table
     */
    private FullScanRule $fullScan;

    /**
     * AccessRule constructor.
     *
     * @param PlanContext $context Plan et requête
     * @param TableSchema $schema Structure des tables
     * @param IndexSuggester $indexes Index proposés
     */
    public function __construct(
        private PlanContext $context,
        private TableSchema $schema,
        private IndexSuggester $indexes
    ) {
        $this->fullScan = new FullScanRule($context, $schema, $indexes);
    }

    /**
     * Retourne les constats sur l'accès à une table
     *
     * @param array<string, mixed> $access Accès normalisé
     * @return array<int, array{level: string, title: string, detail: string, suggestion: string|null, table: string|null}>
     */
    public function findings(array $access): array
    {
        [$table, $alias] = $this->context->tableOf($access);
        if ($table === null) {
            return [];
        }

        $findings = [];
        if ($access['dependent'] === true) {
            $findings[] = $this->dependentFinding($access, $table, $alias);
        }
        if ($access['auto_index'] === true) {
            $findings[] = $this->autoIndexFinding($access, $table, $alias);
            return $findings;
        }

        $finding = match ($access['access']) {
            'full_scan' => $this->fullScan->finding($access, $table, $alias),
            'index_scan' => $this->indexScanFinding($access, $table, $alias),
            'const', 'index_lookup', 'range' => $this->indexFinding($access, $table, $alias),
            default => null,
        };

        return ($finding === null ? $findings : array_merge($findings, [$finding]));
    }

    /**
     * Constat d'une sous-requête corrélée, réexécutée pour chaque ligne de la requête principale
     *
     * @param array<string, mixed> $access Accès normalisé
     * @param string $table Nom de la table
     * @param string $alias Alias de la table (minuscules)
     * @return array{level: string, title: string, detail: string, suggestion: string|null, table: string|null}
     */
    private function dependentFinding(array $access, string $table, string $alias): array
    {
        $rows = $this->context->rowsOf($access, $table);

        return FindingFormatter::finding(
            ($rows !== null && $rows < SqlExplainAdvisor::SMALL_TABLE ? SqlExplainAdvisor::INFO : SqlExplainAdvisor::WARNING),
            'Sous-requête corrélée sur ' . FindingFormatter::tableLabel($table, $alias),
            'La sous-requête dépend de la ligne en cours de la requête principale : elle est réexécutée '
            . 'pour chacune de ses lignes. Réécrivez-la avec une jointure (JOIN … GROUP BY) ou avec EXISTS, '
            . 'et indexez la colonne qui la relie à la requête principale.',
            null,
            $table
        );
    }

    /**
     * Constat d'un index temporaire construit par SQLite à chaque exécution
     *
     * @param array<string, mixed> $access Accès normalisé
     * @param string $table Nom de la table
     * @param string $alias Alias de la table (minuscules)
     * @return array{level: string, title: string, detail: string, suggestion: string|null, table: string|null}
     */
    private function autoIndexFinding(array $access, string $table, string $alias): array
    {
        $rows = $this->context->rowsOf($access, $table);
        $columns = $this->schema->existingColumns($table, (array) $access['index_columns']);

        return FindingFormatter::finding(
            ($rows !== null && $rows >= SqlExplainAdvisor::LARGE_TABLE ? SqlExplainAdvisor::CRITICAL : SqlExplainAdvisor::WARNING),
            'Index temporaire construit à chaque exécution sur ' . FindingFormatter::tableLabel($table, $alias),
            'SQLite ne trouve pas d\'index pour cette recherche : il construit un index automatique '
            . 'à chaque exécution de la requête, puis le supprime. Créez cet index une fois pour toutes.',
            $this->indexes->createIndex($table, $columns),
            $table
        );
    }

    /**
     * Constat d'un parcours complet d'index (type index)
     *
     * @param array<string, mixed> $access Accès normalisé
     * @param string $table Nom de la table
     * @param string $alias Alias de la table (minuscules)
     * @return array{level: string, title: string, detail: string, suggestion: string|null, table: string|null}
     */
    private function indexScanFinding(array $access, string $table, string $alias): array
    {
        $rows = $this->context->rowsOf($access, $table);

        return FindingFormatter::finding(
            ($rows !== null && $rows >= SqlExplainAdvisor::LARGE_TABLE * 10 ? SqlExplainAdvisor::WARNING : SqlExplainAdvisor::INFO),
            'Parcours complet de l\'index ' . FindingFormatter::keyLabel($access) . ' de '
            . FindingFormatter::tableLabel($table, $alias) . FindingFormatter::rowsLabel($rows),
            'Toutes les entrées de l\'index sont lues (type « index »). C\'est moins coûteux qu\'un parcours '
            . 'de la table' . ($access['covering'] === true ? ', d\'autant que l\'index est couvrant (« Using index »)' : '')
            . ', mais le coût reste proportionnel au nombre de lignes : ajoutez un filtre (WHERE) indexé ou une LIMIT '
            . 'si toutes les lignes ne sont pas utiles.',
            null,
            $table
        );
    }

    /**
     * Constat positif : accès à une table par un index
     *
     * @param array<string, mixed> $access Accès normalisé
     * @param string $table Nom de la table
     * @param string $alias Alias de la table (minuscules)
     * @return array{level: string, title: string, detail: string, suggestion: string|null, table: string|null}
     */
    private function indexFinding(array $access, string $table, string $alias): array
    {
        $detail = match (true) {
            $access['access'] === 'const' => 'Une seule ligne est lue, grâce à la clé primaire ou à un index unique.',
            $access['type'] === 'eq_ref' => 'Une seule ligne est lue pour chaque ligne de la table précédente (clé primaire ou index unique).',
            $access['access'] === 'range' => 'Seule une plage de l\'index est parcourue (comparaison <, >, BETWEEN, IN ou LIKE \'abc%\').',
            default => 'Seules les lignes correspondantes sont lues, en passant par l\'index.',
        };
        $covering = ' L\'index est couvrant : toutes les colonnes utiles y sont, la table n\'est pas lue (« Using index »).';

        return FindingFormatter::finding(
            SqlExplainAdvisor::SUCCESS,
            'Accès par index à ' . FindingFormatter::tableLabel($table, $alias) . ' (' . FindingFormatter::keyLabel($access) . ')',
            $detail . ($access['covering'] === true ? $covering : ''),
            null,
            $table
        );
    }
}
