<?php

namespace Core\Profiler\Sql;

use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Core\Profiler\Sql\SqlExplainAdvisor;
use Studoo\EduFramework\Core\Profiler\Sql\SqlExplainer;
use Studoo\EduFramework\Core\Profiler\Sql\SqlQueryInspector;

/**
 * Tests des axes d'amélioration proposés à partir d'un plan EXPLAIN MySQL / MariaDB
 * écrit à la main (requêtes d'un TP de bibliothèque : tables sans index sur les clés étrangères)
 */
class SqlExplainAdvisorTest extends TestCase
{
    /**
     * Structure des tables : seule la clé primaire est indexée
     * @return array<string, array<string, mixed>>
     */
    private function tables(): array
    {
        $table = static fn (string $name, int $rows, array $columns): array => [
            'name' => $name,
            'rows' => $rows,
            'columns' => $columns,
            'indexes' => [['name' => 'PRIMARY', 'columns' => ['id'], 'unique' => true, 'primary' => true]],
        ];

        return [
            'auteurs' => $table('auteurs', 1000, ['id', 'nom', 'prenom']),
            'livres' => $table('livres', 6000, ['id', 'titre', 'annee_publication', 'isbn', 'auteur_id']),
            'utilisateurs' => $table('utilisateurs', 4000, ['id', 'nom', 'prenom']),
            'emprunts' => $table('emprunts', 60000, ['id', 'livre_id', 'utilisateur_id', 'date_emprunt']),
        ];
    }

    /**
     * Ligne de plan EXPLAIN MySQL / MariaDB
     * @return array<string, mixed>
     */
    private function row(string $table, ?string $type, ?string $key, int $rows, ?string $extra, string $selectType = 'SIMPLE'): array
    {
        return [
            'id' => 1, 'select_type' => $selectType, 'table' => $table, 'type' => $type, 'possible_keys' => $key,
            'key' => $key, 'key_len' => null, 'ref' => null, 'rows' => (string) $rows, 'filtered' => 100.0, 'Extra' => $extra,
        ];
    }

    /**
     * Retourne les constats pour une requête et son plan
     * @param array<int, array<string, mixed>> $plan
     * @param array<string, mixed> $context
     * @return array<int, array<string, mixed>>
     */
    private function advise(string $sql, array $plan, array $context = []): array
    {
        return (new SqlExplainAdvisor(
            new SqlQueryInspector($sql),
            SqlExplainer::mysqlAccesses($plan),
            $this->tables(),
            $context
        ))->advise();
    }

    /**
     * Retourne le premier constat dont le titre contient le texte donné
     * @param array<int, array<string, mixed>> $findings
     * @return array<string, mixed>
     */
    private function findingWith(array $findings, string $text): array
    {
        foreach ($findings as $finding) {
            if (str_contains($finding['title'], $text)) {
                return $finding;
            }
        }
        $this->fail('Aucun constat ne contient « ' . $text . ' » : ' . implode(' | ', array_column($findings, 'title')));
    }

    /**
     * Jointure LEFT JOIN sans index sur la clé étrangère (requête de 2 s du TP) :
     * l'index manquant est critique et proposé, la table lue en premier est un parcours attendu
     */
    public function testMissingForeignKeyIndexOnJoinIsCritical(): void
    {
        $findings = $this->advise(
            "SELECT u.id, u.nom, u.prenom, COUNT(e.id) AS total_emprunts\nFROM utilisateurs u\n"
            . "LEFT JOIN emprunts e ON e.utilisateur_id = u.id\nGROUP BY u.id, u.nom, u.prenom\nORDER BY total_emprunts DESC",
            [
                $this->row('u', 'ALL', null, 4000, 'Using temporary; Using filesort'),
                $this->row('e', 'ALL', null, 60000, 'Using where; Using join buffer (hash join)'),
            ]
        );

        $this->assertSame(SqlExplainAdvisor::CRITICAL, $findings[0]['level']);
        $this->assertStringContainsString('emprunts', $findings[0]['title']);
        $this->assertStringContainsString('Using join buffer', $findings[0]['detail']);
        $this->assertSame('CREATE INDEX idx_emprunts_utilisateur_id ON emprunts (utilisateur_id);', $findings[0]['suggestion']);

        // utilisateurs est lue en premier et sans filtre : parcours attendu, rien à proposer
        $users = $this->findingWith($findings, '« utilisateurs »');
        $this->assertSame(SqlExplainAdvisor::INFO, $users['level']);
        $this->assertNull($users['suggestion']);

        // Le tri et le regroupement portent sur une valeur calculée : aucune suggestion d'index
        $this->assertNull($this->findingWith($findings, 'Tri sans index')['suggestion']);
        $temporary = $this->findingWith($findings, 'Table temporaire');
        $this->assertNull($temporary['suggestion']);
        $this->assertStringContainsString('total_emprunts', $temporary['detail']);
    }

    /**
     * Recherche + tri sur la même table : un seul index composite (égalité puis tri) est
     * proposé, pour la jointure comme pour le tri ; l'accès par clé primaire est un bon point
     */
    public function testCompositeIndexCoversLookupAndSort(): void
    {
        $advisor = new SqlExplainAdvisor(
            new SqlQueryInspector(
                "SELECT l.titre FROM livres l JOIN auteurs a ON l.auteur_id = a.id WHERE a.id = :auteur_id ORDER BY l.annee_publication"
            ),
            // MySQL rattache « Using filesort » à la table const (a), la table triée est livres
            SqlExplainer::mysqlAccesses([
                $this->row('a', 'const', 'PRIMARY', 1, 'Using filesort'),
                $this->row('l', 'ALL', null, 6000, 'Using where'),
            ]),
            $this->tables()
        );
        $findings = $advisor->advise();
        $expected = 'CREATE INDEX idx_livres_auteur_id_annee_publication ON livres (auteur_id, annee_publication);';

        $scan = $this->findingWith($findings, 'Parcours complet de la table « livres »');
        $this->assertSame(SqlExplainAdvisor::CRITICAL, $scan['level']);
        $this->assertSame($expected, $scan['suggestion']);
        $this->assertStringContainsString('un seul index', $scan['detail']);

        $sort = $this->findingWith($findings, 'Tri sans index');
        $this->assertStringContainsString('« livres »', $sort['title']);
        $this->assertSame($expected, $sort['suggestion']);

        $this->assertSame(SqlExplainAdvisor::SUCCESS, $this->findingWith($findings, 'Accès par index à « auteurs »')['level']);
        $this->assertSame([$expected], $advisor->suggestions());
    }

    /**
     * Tri d'une seule table sans index : l'index composite suit l'ordre du ORDER BY
     */
    public function testSortOnPlainColumnsSuggestsAnIndexInOrder(): void
    {
        $findings = $this->advise(
            'SELECT id, nom, prenom FROM auteurs ORDER BY nom, prenom',
            [$this->row('auteurs', 'ALL', null, 1000, 'Using filesort')]
        );

        $sort = $this->findingWith($findings, 'Tri sans index');
        $this->assertSame(SqlExplainAdvisor::WARNING, $sort['level']);
        $this->assertSame('CREATE INDEX idx_auteurs_nom_prenom ON auteurs (nom, prenom);', $sort['suggestion']);
        $this->assertSame(SqlExplainAdvisor::INFO, $this->findingWith($findings, 'Parcours complet')['level']);
    }

    /**
     * Filtre WHERE : index proposé sur la colonne filtrée, mais pas sur une colonne
     * enveloppée dans une fonction (signalée à part), et SELECT * signalé
     */
    public function testWhereFiltersFunctionsAndSelectStar(): void
    {
        $findings = $this->advise(
            'SELECT * FROM emprunts WHERE YEAR(date_emprunt) = 2021 AND utilisateur_id = 5',
            [$this->row('emprunts', 'ALL', null, 60000, 'Using where')]
        );

        $scan = $this->findingWith($findings, 'Parcours complet');
        $this->assertSame('CREATE INDEX idx_emprunts_utilisateur_id ON emprunts (utilisateur_id);', $scan['suggestion']);
        $this->assertSame(SqlExplainAdvisor::WARNING, $this->findingWith($findings, 'YEAR(date_emprunt)')['level']);
        $this->assertSame(SqlExplainAdvisor::INFO, $this->findingWith($findings, 'SELECT *')['level']);
    }

    /**
     * Index existant mais ignoré par le moteur : pas de nouvel index proposé
     */
    public function testExistingButUnusedIndexIsExplained(): void
    {
        $tables = $this->tables();
        $tables['emprunts']['indexes'][] = ['name' => 'idx_u', 'columns' => ['utilisateur_id'], 'unique' => false, 'primary' => false];

        $findings = (new SqlExplainAdvisor(
            new SqlQueryInspector('SELECT id FROM emprunts WHERE utilisateur_id > 0'),
            SqlExplainer::mysqlAccesses([$this->row('emprunts', 'ALL', null, 60000, 'Using where')]),
            $tables
        ))->advise();

        $this->assertSame(SqlExplainAdvisor::WARNING, $findings[0]['level']);
        $this->assertStringContainsString('Un index existe', $findings[0]['detail']);
        $this->assertNull($findings[0]['suggestion']);
    }

    /**
     * Contexte d'exécution : requête répétée (N+1), grand résultat sans LIMIT,
     * sous-requête corrélée et WHERE impossible
     */
    public function testExecutionContextAndSpecialPlans(): void
    {
        $findings = $this->advise(
            'SELECT * FROM emprunts WHERE utilisateur_id = ?',
            [$this->row('emprunts', 'ref', 'idx_u', 15, null)],
            ['executions' => 12, 'rows_count' => 800]
        );
        $this->assertSame(SqlExplainAdvisor::CRITICAL, $this->findingWith($findings, 'exécutée 12 fois')['level']);
        $this->assertSame(SqlExplainAdvisor::INFO, $this->findingWith($findings, 'sans LIMIT')['level']);
        $this->assertSame(SqlExplainAdvisor::SUCCESS, end($findings)['level']);

        $dependent = $this->advise(
            'SELECT * FROM livres l WHERE EXISTS (SELECT 1 FROM emprunts e WHERE e.livre_id = l.id)',
            [
                $this->row('l', 'ALL', null, 6000, 'Using where', 'PRIMARY'),
                $this->row('e', 'ALL', null, 60000, 'Using where', 'DEPENDENT SUBQUERY'),
            ]
        );
        $this->assertSame(SqlExplainAdvisor::WARNING, $this->findingWith($dependent, 'Sous-requête corrélée')['level']);
        $this->assertSame(
            'CREATE INDEX idx_emprunts_livre_id ON emprunts (livre_id);',
            $this->findingWith($dependent, '« emprunts »')['suggestion']
        );

        $impossible = $this->advise(
            'SELECT * FROM auteurs WHERE id = 999999',
            [['id' => 1, 'select_type' => 'SIMPLE', 'table' => null, 'type' => null, 'rows' => null,
                'Extra' => 'Impossible WHERE noticed after reading const tables']]
        );
        $this->assertSame('Aucune ligne ne correspond aux conditions', $this->findingWith($impossible, 'Aucune ligne')['title']);
    }
}
