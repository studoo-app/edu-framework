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

use Studoo\EduFramework\Core\Profiler\Sql\Advisor\AccessRule;
use Studoo\EduFramework\Core\Profiler\Sql\Advisor\IndexSuggester;
use Studoo\EduFramework\Core\Profiler\Sql\Advisor\PlanContext;
use Studoo\EduFramework\Core\Profiler\Sql\Advisor\QueryRule;
use Studoo\EduFramework\Core\Profiler\Sql\Advisor\SortRule;
use Studoo\EduFramework\Core\Profiler\Sql\Advisor\TableSchema;

/**
 * Class SqlExplainAdvisor
 * Propose des axes d'amélioration d'une requête SQL à partir de son plan
 * d'exécution normalisé (SqlExplainer::accesses()), du texte de la requête
 * (SqlQueryInspector), de la structure des tables (index existants) et du
 * contexte d'exécution (nombre d'exécutions pendant la requête HTTP, nombre de
 * lignes retournées). Les règles sont dans le namespace Advisor : accès aux
 * tables (AccessRule, FullScanRule), tris et regroupements (SortRule) et texte
 * de la requête (QueryRule).
 *
 * Chaque constat a un niveau : critical (à corriger en priorité), warning
 * (à améliorer), info (à connaître) ou success (ce qui est déjà efficace),
 * un titre, une explication et, si possible, une suggestion SQL (CREATE INDEX).
 * Les suggestions sont des pistes à vérifier en relançant l'analyse.
 *
 * @package Studoo\EduFramework\Core\Profiler\Sql
 */
class SqlExplainAdvisor
{
    public const string CRITICAL = 'critical';
    public const string WARNING = 'warning';
    public const string INFO = 'info';
    public const string SUCCESS = 'success';

    /**
     * Nombre de lignes à partir duquel un parcours complet est considéré comme coûteux
     */
    public const int LARGE_TABLE = 1000;

    /**
     * Nombre de lignes en dessous duquel une table est considérée comme petite
     */
    public const int SMALL_TABLE = 50;

    /**
     * Ordre d'affichage des niveaux
     */
    private const array LEVEL_ORDER = [self::CRITICAL => 0, self::WARNING => 1, self::INFO => 2, self::SUCCESS => 3];

    /**
     * Plan et requête
     */
    private PlanContext $context;

    /**
     * Index proposés
     */
    private IndexSuggester $indexes;

    /**
     * SqlExplainAdvisor constructor.
     *
     * @param SqlQueryInspector $inspector Lecture du texte de la requête
     * @param array<int, array<string, mixed>> $accesses Accès aux tables (SqlExplainer::accesses())
     * @param array<string, array{name: string, rows: int|null, columns: array<int, string>,
     *        indexes: array<int, array{name: string, columns: array<int, string>, unique: bool, primary: bool}>}> $tables
     *        Structure des tables, indexées par nom en minuscules (SqlTableInspector::tables())
     * @param array{executions?: int, rows_count?: int|null, driver?: string} $execution Contexte d'exécution
     */
    public function __construct(
        SqlQueryInspector $inspector,
        private array $accesses,
        private array $tables,
        private array $execution = []
    ) {
        $this->context = new PlanContext($inspector, array_values($accesses), new TableSchema($tables));
        $this->indexes = new IndexSuggester();
    }

    /**
     * Retourne les constats, du plus important au moins important
     *
     * @return array<int, array{level: string, title: string, detail: string, suggestion: string|null, table: string|null}>
     */
    public function advise(): array
    {
        $this->indexes = new IndexSuggester();
        $schema = new TableSchema($this->tables);

        // Tri et regroupement d'abord : l'index composite qu'ils proposent (Exemple :
        // (auteur_id, annee_publication)) sert aussi à la recherche dans la table
        $sortRule = new SortRule($this->context, $schema, $this->indexes);
        $ordering = [$sortRule->sortFinding(), $sortRule->temporaryFinding()];

        $accessRule = new AccessRule($this->context, $schema, $this->indexes);
        $findings = [];
        foreach ($this->accesses as $access) {
            $findings = array_merge($findings, $accessRule->findings($access));
        }

        $findings = array_merge(
            $findings,
            array_values(array_filter($ordering)),
            (new QueryRule($this->context, array_values($this->accesses), $this->execution))->findings()
        );

        // Tri stable par niveau : l'ordre du plan est conservé à niveau égal
        $positions = array_keys($findings);
        array_multisort(
            array_map(static fn (array $finding): int => self::LEVEL_ORDER[$finding['level']], $findings),
            $positions,
            $findings
        );

        return $findings;
    }

    /**
     * Retourne les instructions CREATE INDEX proposées par advise(), sans doublon
     *
     * @return array<int, string>
     */
    public function suggestions(): array
    {
        return $this->indexes->getSuggestions();
    }
}
