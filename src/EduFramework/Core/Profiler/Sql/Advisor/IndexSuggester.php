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
 * Class IndexSuggester
 * Construit les instructions CREATE INDEX proposées par l'analyse EXPLAIN.
 * Un index déjà proposé sur la même table qui commence par les colonnes voulues
 * est réutilisé plutôt que d'en proposer un autre (Exemple : l'index composite
 * (auteur_id, annee_publication) proposé pour un tri sert aussi à la recherche
 * sur auteur_id).
 *
 * @package Studoo\EduFramework\Core\Profiler\Sql\Advisor
 */
class IndexSuggester
{
    /**
     * Longueur maximale d'un nom d'index (MySQL / MariaDB)
     */
    private const int NAME_MAX_LENGTH = 64;

    /**
     * Index proposés, indexés par instruction CREATE INDEX
     * @var array<string, array{table: string, columns: array<int, string>}>
     */
    private array $suggested = [];

    /**
     * Retourne l'instruction CREATE INDEX pour les colonnes données (null sans colonne) :
     * un index déjà proposé sur la même table qui commence par ces colonnes est réutilisé
     *
     * @param string $table Nom de la table
     * @param array<int, string> $columns Colonnes de l'index, dans l'ordre
     * @return string|null
     */
    public function createIndex(string $table, array $columns): ?string
    {
        if ($columns === []) {
            return null;
        }

        $wanted = array_map('strtolower', array_values($columns));
        foreach ($this->suggested as $sql => $index) {
            if (strtolower($index['table']) === strtolower($table)
                && array_map('strtolower', array_slice($index['columns'], 0, count($wanted))) === $wanted) {
                return $sql;
            }
        }

        $name = substr(strtolower('idx_' . $table . '_' . implode('_', $columns)), 0, self::NAME_MAX_LENGTH);
        $sql = 'CREATE INDEX ' . $name . ' ON ' . $table . ' (' . implode(', ', $columns) . ');';
        $this->suggested[$sql] = ['table' => $table, 'columns' => array_values($columns)];

        return $sql;
    }

    /**
     * Retourne les instructions CREATE INDEX proposées, sans doublon
     *
     * @return array<int, string>
     */
    public function getSuggestions(): array
    {
        return array_keys($this->suggested);
    }

    /**
     * Indique si les index proposés comptent plus de colonnes que celles demandées
     * (index composite déjà proposé pour le tri, réutilisé pour la recherche)
     *
     * @param array<int, string|null> $suggestions Instructions CREATE INDEX
     * @param int $asked Nombre de colonnes demandées
     * @return bool
     */
    public function isWiderThan(array $suggestions, int $asked): bool
    {
        $columns = 0;
        foreach (array_unique(array_filter($suggestions)) as $sql) {
            $columns += count($this->suggested[$sql]['columns'] ?? []);
        }

        return $columns > $asked;
    }

    /**
     * Regroupe des suggestions SQL (une par ligne, sans doublon), null si aucune
     *
     * @param array<int, string|null> $suggestions Suggestions
     * @return string|null
     */
    public static function combine(array $suggestions): ?string
    {
        $suggestions = array_values(array_unique(array_filter($suggestions)));

        return ($suggestions === [] ? null : implode("\n", $suggestions));
    }
}
