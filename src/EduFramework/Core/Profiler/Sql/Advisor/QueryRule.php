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
use Studoo\EduFramework\Core\Profiler\Sql\SqlQueryInspector;

/**
 * Class QueryRule
 * Constats sur le texte de la requête et son contexte d'exécution : fonction
 * appliquée à une colonne dans le WHERE, LIKE '%…', SELECT *, aucune ligne
 * possible (WHERE impossible), requête répétée (N+1), grand résultat sans LIMIT.
 *
 * @package Studoo\EduFramework\Core\Profiler\Sql\Advisor
 */
class QueryRule
{
    /**
     * Nombre de lignes retournées à partir duquel l'absence de LIMIT est signalée
     */
    private const int LARGE_RESULT = 500;

    /**
     * Lecture du texte de la requête
     */
    private SqlQueryInspector $inspector;

    /**
     * QueryRule constructor.
     *
     * @param PlanContext $context Plan et requête
     * @param array<int, array<string, mixed>> $accesses Accès aux tables
     * @param array{executions?: int, rows_count?: int|null, driver?: string} $execution Contexte d'exécution
     */
    public function __construct(PlanContext $context, private array $accesses, private array $execution)
    {
        $this->inspector = $context->getInspector();
    }

    /**
     * Retourne les constats sur la requête
     *
     * @return array<int, array{level: string, title: string, detail: string, suggestion: string|null, table: string|null}>
     */
    public function findings(): array
    {
        return array_values(array_filter(array_merge(
            $this->functionFindings(),
            $this->likeFindings(),
            [$this->selectStarFinding(), $this->impossibleWhereFinding(), $this->executionsFinding(), $this->largeResultFinding()]
        )));
    }

    /**
     * Constats des fonctions appliquées à une colonne dans le WHERE
     *
     * @return array<int, array{level: string, title: string, detail: string, suggestion: string|null, table: string|null}>
     */
    private function functionFindings(): array
    {
        return array_map(
            static fn (array $function): array => FindingFormatter::finding(
                SqlExplainAdvisor::WARNING,
                'Fonction appliquée à une colonne dans le WHERE : ' . $function['expression'],
                'L\'index de la colonne ' . $function['column'] . ' ne peut pas être utilisé : le moteur calcule '
                . $function['function'] . '() pour chaque ligne avant de comparer. Écrivez la condition sur la colonne '
                . 'elle-même, par exemple YEAR(date) = 2024 devient date >= \'2024-01-01\' AND date < \'2025-01-01\'.'
            ),
            $this->inspector->getConditions()->functionsOnWhereColumns()
        );
    }

    /**
     * Constats des recherches LIKE dont le motif commence par %
     *
     * @return array<int, array{level: string, title: string, detail: string, suggestion: string|null, table: string|null}>
     */
    private function likeFindings(): array
    {
        return array_map(
            static fn (string $column): array => FindingFormatter::finding(
                SqlExplainAdvisor::WARNING,
                'Recherche LIKE \'%…\' sur ' . $column,
                'Un motif qui commence par % ne peut pas utiliser d\'index : toutes les lignes sont lues. '
                . 'Un motif qui commence par du texte (LIKE \'abc%\') utilise l\'index ; pour chercher un mot '
                . 'n\'importe où dans le texte, préférez un index FULLTEXT (MATCH … AGAINST).'
            ),
            $this->inspector->getConditions()->leadingWildcardLikes()
        );
    }

    /**
     * Constat d'un SELECT *, null sinon
     *
     * @return array{level: string, title: string, detail: string, suggestion: string|null, table: string|null}|null
     */
    private function selectStarFinding(): ?array
    {
        if ($this->inspector->hasSelectStar() === false) {
            return null;
        }

        return FindingFormatter::finding(
            SqlExplainAdvisor::INFO,
            'SELECT * : toutes les colonnes sont lues',
            'Sélectionnez uniquement les colonnes affichées : moins de données lues et transférées, '
            . 'et un index peut alors suffire à répondre sans lire la table (index couvrant).'
        );
    }

    /**
     * Constat d'un plan sans accès aux tables car aucune ligne ne peut correspondre, null sinon
     *
     * @return array{level: string, title: string, detail: string, suggestion: string|null, table: string|null}|null
     */
    private function impossibleWhereFinding(): ?array
    {
        $extras = array_map(static fn (array $access): string => (string) ($access['extra'] ?? ''), $this->accesses);
        $impossible = preg_grep('/Impossible WHERE|no matching row in const table/i', $extras) ?: [];
        if ($impossible === []) {
            return null;
        }

        return FindingFormatter::finding(
            SqlExplainAdvisor::INFO,
            'Aucune ligne ne correspond aux conditions',
            'Le moteur a déduit, sans parcourir les tables, que la requête ne retourne aucune ligne (« '
            . reset($impossible) . ' ») : par exemple une valeur cherchée qui n\'existe pas dans un index unique. '
            . 'Le plan ne montre donc pas les accès aux tables : essayez une valeur qui existe.'
        );
    }

    /**
     * Constat d'une requête exécutée plusieurs fois pendant la requête HTTP (N+1), null sinon
     *
     * @return array{level: string, title: string, detail: string, suggestion: string|null, table: string|null}|null
     */
    private function executionsFinding(): ?array
    {
        $executions = (int) ($this->execution['executions'] ?? 1);
        if ($executions <= 1) {
            return null;
        }

        $level = ($executions >= 3 ? SqlExplainAdvisor::WARNING : SqlExplainAdvisor::INFO);

        return FindingFormatter::finding(
            ($executions >= 10 ? SqlExplainAdvisor::CRITICAL : $level),
            'Requête exécutée ' . $executions . ' fois pendant la requête HTTP',
            'La même requête est exécutée plusieurs fois, souvent dans une boucle (problème « N+1 ») : '
            . 'chaque exécution coûte un aller-retour avec la base. Récupérez toutes les données en une seule '
            . 'requête (jointure, ou WHERE id IN (…)).'
        );
    }

    /**
     * Constat d'un grand résultat de lecture sans LIMIT, null sinon
     *
     * @return array{level: string, title: string, detail: string, suggestion: string|null, table: string|null}|null
     */
    private function largeResultFinding(): ?array
    {
        $rowsCount = $this->execution['rows_count'] ?? null;
        $isRead = in_array($this->inspector->statementType(), ['SELECT', 'WITH'], true);
        if ($rowsCount === null || $rowsCount < self::LARGE_RESULT || $this->inspector->hasLimit() === true || $isRead === false) {
            return null;
        }

        return FindingFormatter::finding(
            SqlExplainAdvisor::INFO,
            FindingFormatter::formatNumber($rowsCount) . ' lignes retournées sans LIMIT',
            'Toutes les lignes sont envoyées à PHP. Si la page n\'en affiche qu\'une partie, paginez avec '
            . 'LIMIT … OFFSET … : la base lit et transfère moins de données.'
        );
    }
}
