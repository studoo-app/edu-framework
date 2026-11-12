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
 * Class SqlExplainAdvisor
 * Propose des axes d'amélioration d'une requête SQL à partir de son plan
 * d'exécution normalisé (SqlExplainer::accesses()), du texte de la requête
 * (SqlQueryInspector), de la structure des tables (index existants) et du
 * contexte d'exécution (nombre d'exécutions pendant la requête HTTP, nombre de
 * lignes retournées).
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
     * Ordre d'affichage des niveaux
     */
    private const array LEVEL_ORDER = [self::CRITICAL => 0, self::WARNING => 1, self::INFO => 2, self::SUCCESS => 3];

    /**
     * Nombre de lignes à partir duquel un parcours complet est considéré comme coûteux
     */
    public const int LARGE_TABLE = 1000;

    /**
     * Nombre de lignes en dessous duquel une table est considérée comme petite
     */
    public const int SMALL_TABLE = 50;

    /**
     * Index proposés, indexés par instruction CREATE INDEX : un index déjà proposé
     * qui commence par les colonnes voulues est réutilisé plutôt que d'en proposer un autre
     * @var array<string, array{table: string, columns: array<int, string>}>
     */
    private array $suggested = [];

    /**
     * Position de chaque alias dans le plan (ordre de lecture des tables)
     * @var array<string, int>
     */
    private array $positions = [];

    /**
     * Tables de la requête (alias en minuscules => nom de la table)
     * @var array<string, string>
     */
    private array $aliases = [];

    /**
     * SqlExplainAdvisor constructor.
     *
     * @param SqlQueryInspector $inspector Lecture du texte de la requête
     * @param array<int, array<string, mixed>> $accesses Accès aux tables (SqlExplainer::accesses())
     * @param array<string, array{name: string, rows: int|null, columns: array<int, string>,
     *        indexes: array<int, array{name: string, columns: array<int, string>, unique: bool, primary: bool}>}> $tables
     *        Structure des tables, indexées par nom en minuscules (SqlExplainer::tables())
     * @param array{executions?: int, rows_count?: int|null, driver?: string} $context Contexte d'exécution
     */
    public function __construct(
        private SqlQueryInspector $inspector,
        private array $accesses,
        private array $tables,
        private array $context = []
    ) {
        $this->aliases = $inspector->tables();
        foreach ($accesses as $position => $access) {
            $alias = strtolower((string) ($access['alias'] ?? ''));
            if ($alias !== '' && isset($this->positions[$alias]) === false) {
                $this->positions[$alias] = $position;
            }
        }
    }

    /**
     * Retourne les constats, du plus important au moins important
     *
     * @return array<int, array{level: string, title: string, detail: string, suggestion: string|null, table: string|null}>
     */
    public function advise(): array
    {
        $this->suggested = [];
        $findings = [];

        // Tri et regroupement d'abord : l'index composite qu'ils proposent (Exemple :
        // (auteur_id, annee_publication)) sert aussi à la recherche dans la table
        $sort = $this->sortFinding();
        $temporary = $this->temporaryFinding();

        foreach ($this->accesses as $access) {
            foreach ($this->accessFindings($access) as $finding) {
                $findings[] = $finding;
            }
        }

        foreach ([$sort, $temporary] as $finding) {
            if ($finding !== null) {
                $findings[] = $finding;
            }
        }

        foreach ($this->queryFindings() as $finding) {
            $findings[] = $finding;
        }

        // Tri stable par niveau : l'ordre du plan est conservé à niveau égal
        $order = array_keys($findings);
        array_multisort(
            array_map(static fn (array $finding): int => self::LEVEL_ORDER[$finding['level']], $findings),
            $order,
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
        return array_keys($this->suggested);
    }

    /**
     * Constats sur l'accès à une table (une ligne du plan)
     *
     * @param array<string, mixed> $access Accès normalisé
     * @return array<int, array{level: string, title: string, detail: string, suggestion: string|null, table: string|null}>
     */
    private function accessFindings(array $access): array
    {
        $alias = strtolower((string) ($access['alias'] ?? ''));
        $table = $this->aliases[$alias] ?? ($this->tables[$alias]['name'] ?? null);
        if ($table === null) {
            return [];
        }

        $rows = $this->rowsOf($access, $table);
        $label = $this->tableLabel($table, $alias);
        $findings = [];

        if ($access['dependent'] === true) {
            $findings[] = $this->finding(
                ($rows !== null && $rows < self::SMALL_TABLE ? self::INFO : self::WARNING),
                'Sous-requête corrélée sur ' . $label,
                'La sous-requête dépend de la ligne en cours de la requête principale : elle est réexécutée '
                . 'pour chacune de ses lignes. Réécrivez-la avec une jointure (JOIN … GROUP BY) ou avec EXISTS, '
                . 'et indexez la colonne qui la relie à la requête principale.',
                null,
                $table
            );
        }

        if ($access['auto_index'] === true) {
            $columns = array_values(array_filter(
                (array) $access['index_columns'],
                fn (string $column): bool => $this->hasColumn($table, $column)
            ));
            $findings[] = $this->finding(
                ($rows !== null && $rows >= self::LARGE_TABLE ? self::CRITICAL : self::WARNING),
                'Index temporaire construit à chaque exécution sur ' . $label,
                'SQLite ne trouve pas d\'index pour cette recherche : il construit un index automatique '
                . 'à chaque exécution de la requête, puis le supprime. Créez cet index une fois pour toutes.',
                $this->createIndex($table, $columns),
                $table
            );
            return $findings;
        }

        return array_merge($findings, match ($access['access']) {
            'full_scan' => $this->fullScanFindings($access, $table, $alias, $rows, $label),
            'index_scan' => [$this->finding(
                ($rows !== null && $rows >= self::LARGE_TABLE * 10 ? self::WARNING : self::INFO),
                'Parcours complet de l\'index ' . $this->keyLabel($access) . ' de ' . $label . $this->rowsLabel($rows),
                'Toutes les entrées de l\'index sont lues (type « index »). C\'est moins coûteux qu\'un parcours '
                . 'de la table' . ($access['covering'] === true ? ', d\'autant que l\'index est couvrant (« Using index »)' : '')
                . ', mais le coût reste proportionnel au nombre de lignes : ajoutez un filtre (WHERE) indexé ou une LIMIT '
                . 'si toutes les lignes ne sont pas utiles.',
                null,
                $table
            )],
            'const', 'index_lookup', 'range' => [$this->indexFinding($access, $label, $table)],
            default => [],
        });
    }

    /**
     * Constats d'un parcours complet de table : index manquant sur une colonne de
     * jointure ou de filtre, index existant mais ignoré, ou parcours attendu
     *
     * @param array<string, mixed> $access Accès normalisé
     * @param string $table Nom de la table
     * @param string $alias Alias de la table dans la requête (minuscules)
     * @param int|null $rows Nombre de lignes estimé
     * @param string $label Libellé de la table
     * @return array<int, array{level: string, title: string, detail: string, suggestion: string|null, table: string|null}>
     */
    private function fullScanFindings(array $access, string $table, string $alias, ?int $rows, string $label): array
    {
        $title = 'Parcours complet de la table ' . $label . $this->rowsLabel($rows);
        $joinColumns = $this->joinColumnsOf($alias, $table);
        $filterColumns = $this->filterColumnsOf($alias, $table);
        $missingJoin = array_values(array_filter($joinColumns, fn (string $c): bool => $this->isIndexed($table, $c) === false));
        $missingFilter = array_values(array_filter($filterColumns, fn (string $c): bool => $this->isIndexed($table, $c) === false));

        if ($missingJoin !== [] || $missingFilter !== []) {
            $level = self::INFO;
            if ($rows === null || $rows >= self::LARGE_TABLE || ($access['join_buffer'] === true && $rows >= self::SMALL_TABLE)) {
                $level = self::CRITICAL;
            } elseif ($rows >= self::SMALL_TABLE) {
                $level = self::WARNING;
            }

            $details = [];
            if ($missingJoin !== []) {
                $details[] = 'La jointure sur ' . $this->columnsLabel($alias, $missingJoin) . ' n\'utilise pas d\'index : '
                    . 'pour chaque ligne de la table précédente, le moteur doit relire toute la table ' . $table
                    . ($access['join_buffer'] === true ? ' (tampon de jointure, « Using join buffer »)' : '') . '.';
            }
            if ($missingFilter !== []) {
                $details[] = 'Le filtre WHERE sur ' . $this->columnsLabel($alias, $missingFilter) . ' ne peut pas utiliser '
                    . 'd\'index : toutes les lignes sont lues puis filtrées une par une.';
            }
            $details[] = 'Un index sur ' . (count($missingJoin) + count($missingFilter) > 1 ? 'ces colonnes' : 'cette colonne')
                . ' permet d\'aller directement aux lignes utiles.'
                . ($rows !== null && $rows < self::SMALL_TABLE
                    ? ' La table est encore petite : l\'impact sera visible quand le volume augmentera.' : '');

            $suggestions = [];
            $equalities = array_values(array_intersect($missingFilter, $this->filterColumnsOf($alias, $table, true)));
            if (count($equalities) > 1) {
                // Plusieurs égalités sur la même table : un index composite les couvre toutes
                $suggestions[] = $this->createIndex($table, $equalities);
                $missingFilter = array_values(array_diff($missingFilter, $equalities));
            }
            foreach (array_unique(array_merge($missingJoin, $missingFilter)) as $column) {
                $suggestions[] = $this->createIndex($table, [$column]);
            }
            if ($this->widerThanAsked($suggestions, count($missingJoin) + count($missingFilter)) === true) {
                $details[] = 'L\'index proposé ajoute les colonnes du tri (ORDER BY) : un seul index règle les deux points.';
            }

            return [$this->finding($level, $title, implode(' ', $details), $this->joinSuggestions($suggestions), $table)];
        }

        if ($joinColumns !== [] || $filterColumns !== []) {
            $columns = array_unique(array_merge($joinColumns, $filterColumns));
            return [$this->finding(
                ($rows !== null && $rows >= self::LARGE_TABLE ? self::WARNING : self::INFO),
                $title,
                'Un index existe sur ' . $this->columnsLabel($alias, $columns) . ' mais le moteur a préféré lire toute '
                . 'la table' . ($access['possible_keys'] !== null && $access['possible_keys'] !== ''
                    ? ' (index possibles : ' . $access['possible_keys'] . ')' : '') . '. Causes fréquentes : la table '
                . 'est petite, la condition correspond à une grande partie des lignes (index peu sélectif), '
                . 'ou la condition empêche l\'usage de l\'index (fonction sur la colonne, LIKE \'%…\', '
                . 'comparaison entre types différents).',
                null,
                $table
            )];
        }

        return [$this->finding(
            ($rows !== null && $rows >= self::LARGE_TABLE * 10 ? self::WARNING : self::INFO),
            $title,
            'Aucun filtre (WHERE) ni jointure indexable ne porte sur cette table : toutes ses lignes sont '
            . 'nécessaires au résultat, le parcours complet est donc attendu. Pour lire moins de lignes, '
            . 'filtrez (WHERE) ou paginez (LIMIT) si tout le résultat n\'est pas utile.',
            null,
            $table
        )];
    }

    /**
     * Constat positif : accès à une table par un index
     *
     * @param array<string, mixed> $access Accès normalisé
     * @param string $label Libellé de la table
     * @param string $table Nom de la table
     * @return array{level: string, title: string, detail: string, suggestion: string|null, table: string|null}
     */
    private function indexFinding(array $access, string $label, string $table): array
    {
        $detail = match (true) {
            $access['access'] === 'const' => 'Une seule ligne est lue, grâce à la clé primaire ou à un index unique.',
            $access['type'] === 'eq_ref' => 'Une seule ligne est lue pour chaque ligne de la table précédente (clé primaire ou index unique).',
            $access['access'] === 'range' => 'Seule une plage de l\'index est parcourue (comparaison <, >, BETWEEN, IN ou LIKE \'abc%\').',
            default => 'Seules les lignes correspondantes sont lues, en passant par l\'index.',
        };
        if ($access['covering'] === true) {
            $detail .= ' L\'index est couvrant : toutes les colonnes utiles y sont, la table n\'est pas lue (« Using index »).';
        }

        return $this->finding(
            self::SUCCESS,
            'Accès par index à ' . $label . ' (' . $this->keyLabel($access) . ')',
            $detail,
            null,
            $table
        );
    }

    /**
     * Constat de tri sans index (Using filesort / USE TEMP B-TREE FOR ORDER BY)
     *
     * @return array{level: string, title: string, detail: string, suggestion: string|null, table: string|null}|null
     */
    private function sortFinding(): ?array
    {
        $access = $this->firstAccessWith('filesort');
        if ($access === null) {
            return null;
        }

        $items = $this->inspector->orderByItems();
        $expressions = array_filter($items, static fn (array $item): bool => $item['column'] === null);
        $title = 'Tri sans index (ORDER BY)';
        if ($items === [] || $expressions !== []) {
            $names = array_map(static fn (array $item): string => $item['expression'], $expressions);
            return $this->finding(
                self::INFO,
                $title,
                'Le tri porte sur une valeur calculée' . ($names === [] ? '' : ' (' . implode(', ', $names) . ')')
                . ' : aucun index ne peut l\'éviter, le moteur trie le résultat en mémoire (« Using filesort »). '
                . ($this->inspector->hasLimit() === true
                    ? 'Avec la LIMIT, seules les premières lignes sont conservées pendant le tri : le coût reste raisonnable.'
                    : 'Vérifiez que tout le résultat est utile : avec une LIMIT, le tri ne conserve que les premières lignes.'),
                null,
                null
            );
        }

        [$table, $alias] = $this->tableOfAccess($access);
        $columns = $this->itemsColumns($items, $alias, $table);
        $rows = ($table === null ? null : $this->rowsOf($access, $table));
        $suggestion = null;
        if ($table !== null && $columns !== null && $this->hasIndexStartingWith($table, $columns) === false) {
            // Les égalités du WHERE d'abord, puis les colonnes du tri (Exemple : (auteur_id, annee))
            $equalities = array_values(array_diff(array_unique(array_merge(
                $this->filterColumnsOf((string) $alias, $table, true),
                $this->joinColumnsOf((string) $alias, $table, true)
            )), $columns));
            $suggestion = $this->createIndex($table, array_merge($equalities, $columns));
        }

        return $this->finding(
            ($rows !== null && $rows >= self::LARGE_TABLE ? self::WARNING : self::INFO),
            $title . ($table === null ? '' : ' sur ' . $this->tableLabel($table, (string) $alias) . $this->rowsLabel($rows)),
            'Le résultat est trié après avoir été lu (« Using filesort »). Un index dont les colonnes suivent '
            . 'l\'ordre du ORDER BY permet de lire les lignes directement dans le bon ordre, sans tri. '
            . 'Si la requête filtre aussi par égalité (WHERE col = …), placez ces colonnes en premier dans l\'index.',
            $suggestion,
            $table
        );
    }

    /**
     * Constat de table temporaire (Using temporary / USE TEMP B-TREE FOR GROUP BY ou DISTINCT)
     *
     * @return array{level: string, title: string, detail: string, suggestion: string|null, table: string|null}|null
     */
    private function temporaryFinding(): ?array
    {
        $access = $this->firstAccessWith('temporary');
        if ($access === null) {
            return null;
        }

        [$table, $alias] = $this->tableOfAccess($access);
        $rows = ($table === null ? null : $this->rowsOf($access, $table));
        $groupItems = $this->inspector->groupByItems();
        $computed = array_map(
            static fn (array $item): string => $item['expression'],
            array_filter($this->inspector->orderByItems(), static fn (array $item): bool => $item['column'] === null)
        );
        $columns = $this->itemsColumns($groupItems, $alias, $table);

        $reason = '';
        $suggestion = null;
        if ($groupItems !== [] && $computed !== []) {
            $reason = ' Ici, le tri porte sur une valeur calculée (' . implode(', ', $computed) . ') : le résultat '
                . 'regroupé doit être stocké avant d\'être trié, la table temporaire est attendue.';
        } elseif ($groupItems !== [] && $columns === null) {
            $reason = ' Ici, le regroupement ne porte pas uniquement sur des colonnes de la table lue en premier : '
                . 'la table temporaire est difficile à éviter, réduisez plutôt le nombre de lignes à regrouper (WHERE).';
        } elseif ($table !== null && $columns !== null && $this->hasIndexStartingWith($table, $columns) === false
            && $this->hasUniqueIndexWithin($table, $columns) === false) {
            $suggestion = $this->createIndex($table, $columns);
        }

        return $this->finding(
            ($rows !== null && $rows >= self::LARGE_TABLE && $reason === '' ? self::WARNING : self::INFO),
            'Table temporaire (GROUP BY, DISTINCT ou UNION)',
            'Le moteur stocke un résultat intermédiaire pour regrouper ou dédoublonner les lignes (« Using temporary »). '
            . 'Quand le GROUP BY porte sur les colonnes de la table lue en premier, un index sur ces colonnes '
            . 'permet de regrouper les lignes dans l\'ordre de l\'index, sans table temporaire.' . $reason,
            $suggestion,
            $table
        );
    }

    /**
     * Constats sur le texte de la requête et son contexte d'exécution
     *
     * @return array<int, array{level: string, title: string, detail: string, suggestion: string|null, table: string|null}>
     */
    private function queryFindings(): array
    {
        $findings = [];

        foreach ($this->inspector->functionsOnWhereColumns() as $function) {
            $findings[] = $this->finding(
                self::WARNING,
                'Fonction appliquée à une colonne dans le WHERE : ' . $function['expression'],
                'L\'index de la colonne ' . $function['column'] . ' ne peut pas être utilisé : le moteur calcule '
                . $function['function'] . '() pour chaque ligne avant de comparer. Écrivez la condition sur la colonne '
                . 'elle-même, par exemple YEAR(date) = 2024 devient date >= \'2024-01-01\' AND date < \'2025-01-01\'.',
                null,
                null
            );
        }

        foreach ($this->inspector->leadingWildcardLikes() as $column) {
            $findings[] = $this->finding(
                self::WARNING,
                'Recherche LIKE \'%…\' sur ' . $column,
                'Un motif qui commence par % ne peut pas utiliser d\'index : toutes les lignes sont lues. '
                . 'Un motif qui commence par du texte (LIKE \'abc%\') utilise l\'index ; pour chercher un mot '
                . 'n\'importe où dans le texte, préférez un index FULLTEXT (MATCH … AGAINST).',
                null,
                null
            );
        }

        if ($this->inspector->hasSelectStar() === true) {
            $findings[] = $this->finding(
                self::INFO,
                'SELECT * : toutes les colonnes sont lues',
                'Sélectionnez uniquement les colonnes affichées : moins de données lues et transférées, '
                . 'et un index peut alors suffire à répondre sans lire la table (index couvrant).',
                null,
                null
            );
        }

        foreach ($this->accesses as $access) {
            if (preg_match('/Impossible WHERE|no matching row in const table/i', (string) ($access['extra'] ?? '')) === 1) {
                $findings[] = $this->finding(
                    self::INFO,
                    'Aucune ligne ne correspond aux conditions',
                    'Le moteur a déduit, sans parcourir les tables, que la requête ne retourne aucune ligne (« '
                    . $access['extra'] . ' ») : par exemple une valeur cherchée qui n\'existe pas dans un index unique. '
                    . 'Le plan ne montre donc pas les accès aux tables : essayez une valeur qui existe.',
                    null,
                    null
                );
                break;
            }
        }

        $executions = (int) ($this->context['executions'] ?? 1);
        if ($executions > 1) {
            $findings[] = $this->finding(
                ($executions >= 10 ? self::CRITICAL : ($executions >= 3 ? self::WARNING : self::INFO)),
                'Requête exécutée ' . $executions . ' fois pendant la requête HTTP',
                'La même requête est exécutée plusieurs fois, souvent dans une boucle (problème « N+1 ») : '
                . 'chaque exécution coûte un aller-retour avec la base. Récupérez toutes les données en une seule '
                . 'requête (jointure, ou WHERE id IN (…)).',
                null,
                null
            );
        }

        $rowsCount = $this->context['rows_count'] ?? null;
        if ($rowsCount !== null && $rowsCount >= 500 && $this->inspector->hasLimit() === false
            && in_array($this->inspector->statementType(), ['SELECT', 'WITH'], true) === true) {
            $findings[] = $this->finding(
                self::INFO,
                $this->formatNumber($rowsCount) . ' lignes retournées sans LIMIT',
                'Toutes les lignes sont envoyées à PHP. Si la page n\'en affiche qu\'une partie, paginez avec '
                . 'LIMIT … OFFSET … : la base lit et transfère moins de données.',
                null,
                null
            );
        }

        return $findings;
    }

    /**
     * Retourne les colonnes de la table utilisées pour la rechercher dans une jointure
     * (a.x = b.y) : seules comptent les jointures vers une table lue avant elle dans le
     * plan (la table lue en premier n'est pas recherchée, elle est parcourue)
     *
     * @param string $alias Alias de la table (minuscules)
     * @param string $table Nom de la table
     * @param bool $constOnly Ne retourner que les jointures vers une table réduite à une ligne (const) :
     *        elles équivalent à une égalité avec une valeur
     * @return array<int, string>
     */
    private function joinColumnsOf(string $alias, string $table, bool $constOnly = false): array
    {
        $columns = [];
        foreach ($this->inspector->joinConditions() as $join) {
            foreach ([['left', 'right'], ['right', 'left']] as [$side, $other]) {
                $otherAlias = $join[$other . '_alias'];
                if ($join[$side . '_alias'] !== $alias || $otherAlias === $alias) {
                    continue;
                }
                $readBefore = (isset($this->positions[$otherAlias]) === false || isset($this->positions[$alias]) === false
                    || $this->positions[$otherAlias] < $this->positions[$alias]);
                if ($readBefore === true && ($constOnly === false || $this->isConst($otherAlias) === true)) {
                    $columns[] = $join[$side . '_column'];
                }
            }
        }

        return $this->existingColumns($table, $columns);
    }

    /**
     * Indique si la table d'alias donné est réduite à une seule ligne dans le plan (accès const)
     *
     * @param string $alias Alias de la table (minuscules)
     * @return bool
     */
    private function isConst(string $alias): bool
    {
        $position = $this->positions[$alias] ?? null;

        return $position !== null && ($this->accesses[$position]['access'] ?? null) === 'const';
    }

    /**
     * Indique si un index unique (ou la clé primaire) de la table est entièrement
     * compris dans les colonnes données : chaque groupe correspond alors à une seule ligne
     *
     * @param string $table Nom de la table
     * @param array<int, string> $columns Colonnes
     * @return bool
     */
    private function hasUniqueIndexWithin(string $table, array $columns): bool
    {
        $wanted = array_map('strtolower', $columns);
        foreach ($this->tables[strtolower($table)]['indexes'] ?? [] as $index) {
            if ($index['unique'] === true && $index['columns'] !== []
                && array_diff(array_map('strtolower', $index['columns']), $wanted) === []) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retourne les colonnes de la table filtrées dans le WHERE (hors fonctions et LIKE '%…')
     *
     * @param string $alias Alias de la table (minuscules)
     * @param string $table Nom de la table
     * @param bool $equalityOnly Ne retourner que les colonnes comparées par égalité (= ou IN)
     * @return array<int, string>
     */
    private function filterColumnsOf(string $alias, string $table, bool $equalityOnly = false): array
    {
        $excluded = array_map('strtolower', array_column($this->inspector->functionsOnWhereColumns(), 'column'));
        foreach ($this->inspector->leadingWildcardLikes() as $column) {
            $parts = explode('.', $column);
            $excluded[] = strtolower(end($parts));
        }

        $columns = [];
        foreach ($this->inspector->whereColumns() as $filter) {
            if ($equalityOnly === true && in_array($filter['operator'], ['=', 'IN', '<=>'], true) === false) {
                continue;
            }
            $sameTable = ($filter['alias'] === null
                ? strtolower((string) $this->ownerOf($filter['column'])) === strtolower($table)
                : $filter['alias'] === $alias);
            if ($sameTable === true && in_array(strtolower($filter['column']), $excluded, true) === false) {
                $columns[] = $filter['column'];
            }
        }

        return $this->existingColumns($table, $columns);
    }

    /**
     * Retourne la table qui possède une colonne non préfixée : la seule table de la
     * requête, ou la seule table qui a cette colonne
     *
     * @param string $column Nom de la colonne
     * @return string|null
     */
    private function ownerOf(string $column): ?string
    {
        $names = $this->inspector->tableNames();
        if (count($names) === 1) {
            return $names[0];
        }

        $owners = array_values(array_filter($names, fn (string $name): bool => $this->hasColumn($name, $column, false)));

        return (count($owners) === 1 ? $owners[0] : null);
    }

    /**
     * Retourne les colonnes d'éléments ORDER BY / GROUP BY si ce sont toutes des colonnes
     * de la table donnée (null sinon : expression, autre table, colonne inconnue)
     *
     * @param array<int, array{alias: string|null, column: string|null, expression: string, direction: string}> $items Éléments
     * @param string|null $alias Alias de la table (minuscules)
     * @param string|null $table Nom de la table
     * @return array<int, string>|null
     */
    private function itemsColumns(array $items, ?string $alias, ?string $table): ?array
    {
        if ($items === [] || $table === null) {
            return null;
        }

        $columns = [];
        foreach ($items as $item) {
            if ($item['column'] === null) {
                return null;
            }
            $owner = ($item['alias'] === null ? $this->ownerOf($item['column']) : ($this->aliases[$item['alias']] ?? null));
            if ($owner === null || strtolower($owner) !== strtolower($table)
                || $this->hasColumn($table, $item['column']) === false) {
                return null;
            }
            $columns[] = $item['column'];
        }

        return array_values(array_unique($columns));
    }

    /**
     * Retourne la table et l'alias d'un accès
     *
     * @param array<string, mixed> $access Accès normalisé
     * @return array{0: string|null, 1: string|null}
     */
    private function tableOfAccess(array $access): array
    {
        $alias = strtolower((string) ($access['alias'] ?? ''));
        $table = $this->aliases[$alias] ?? ($this->tables[$alias]['name'] ?? null);

        return [$table, ($table === null ? null : $alias)];
    }

    /**
     * Retourne le premier accès du plan qui porte l'indicateur donné (filesort, temporary).
     * MySQL peut rattacher l'indicateur à une table réduite à une ligne (const) : c'est
     * alors la table suivante, réellement parcourue, qui est triée ou regroupée
     *
     * @param string $flag Indicateur
     * @return array<string, mixed>|null
     */
    private function firstAccessWith(string $flag): ?array
    {
        foreach ($this->accesses as $position => $access) {
            if (($access[$flag] ?? false) !== true) {
                continue;
            }
            if ($access['access'] === 'const') {
                foreach (array_slice($this->accesses, $position + 1) as $next) {
                    if ($next['access'] !== 'const') {
                        return $next;
                    }
                }
            }
            return $access;
        }

        return null;
    }

    /**
     * Retourne le nombre de lignes de la table : estimation du plan, sinon structure de la table
     *
     * @param array<string, mixed> $access Accès normalisé
     * @param string $table Nom de la table
     * @return int|null
     */
    private function rowsOf(array $access, string $table): ?int
    {
        if (isset($access['actual_rows']) === true && is_numeric($access['actual_rows']) === true) {
            return (int) round((float) $access['actual_rows']);
        }
        if (isset($access['rows']) === true && is_numeric($access['rows']) === true) {
            return (int) $access['rows'];
        }

        return $this->tables[strtolower($table)]['rows'] ?? null;
    }

    /**
     * Indique si une colonne est la première colonne d'un index de la table
     *
     * @param string $table Nom de la table
     * @param string $column Nom de la colonne
     * @return bool
     */
    private function isIndexed(string $table, string $column): bool
    {
        return $this->hasIndexStartingWith($table, [$column]);
    }

    /**
     * Indique si un index de la table commence par les colonnes données, dans cet ordre
     *
     * @param string $table Nom de la table
     * @param array<int, string> $columns Colonnes
     * @return bool
     */
    private function hasIndexStartingWith(string $table, array $columns): bool
    {
        $wanted = array_map('strtolower', array_values($columns));
        foreach ($this->tables[strtolower($table)]['indexes'] ?? [] as $index) {
            $start = array_map('strtolower', array_slice($index['columns'], 0, count($wanted)));
            if ($start === $wanted) {
                return true;
            }
        }

        return false;
    }

    /**
     * Indique si la table a la colonne donnée (vrai si la structure de la table est inconnue)
     *
     * @param string $table Nom de la table
     * @param string $column Nom de la colonne
     * @param bool $whenUnknown Valeur retournée si la structure de la table est inconnue
     * @return bool
     */
    private function hasColumn(string $table, string $column, bool $whenUnknown = true): bool
    {
        $columns = $this->tables[strtolower($table)]['columns'] ?? null;
        if ($columns === null) {
            return $whenUnknown;
        }

        return in_array(strtolower($column), array_map('strtolower', $columns), true);
    }

    /**
     * Retourne les colonnes qui existent dans la table, sans doublon, avec la casse de la table
     *
     * @param string $table Nom de la table
     * @param array<int, string> $columns Colonnes
     * @return array<int, string>
     */
    private function existingColumns(string $table, array $columns): array
    {
        $known = $this->tables[strtolower($table)]['columns'] ?? null;
        $result = [];
        foreach ($columns as $column) {
            if ($known === null) {
                $result[strtolower($column)] = $column;
                continue;
            }
            foreach ($known as $name) {
                if (strtolower($name) === strtolower($column)) {
                    $result[strtolower($column)] = $name;
                }
            }
        }

        return array_values($result);
    }

    /**
     * Retourne l'instruction CREATE INDEX pour les colonnes données (null sans colonne) :
     * un index déjà proposé sur la même table qui commence par ces colonnes est réutilisé
     *
     * @param string $table Nom de la table
     * @param array<int, string> $columns Colonnes de l'index, dans l'ordre
     * @return string|null
     */
    private function createIndex(string $table, array $columns): ?string
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

        $name = substr(strtolower('idx_' . $table . '_' . implode('_', $columns)), 0, 64);
        $sql = 'CREATE INDEX ' . $name . ' ON ' . $table . ' (' . implode(', ', $columns) . ');';
        $this->suggested[$sql] = ['table' => $table, 'columns' => array_values($columns)];

        return $sql;
    }

    /**
     * Indique si les index proposés comptent plus de colonnes que celles demandées
     * (index composite déjà proposé pour le tri, réutilisé pour la recherche)
     *
     * @param array<int, string|null> $suggestions Instructions CREATE INDEX
     * @param int $asked Nombre de colonnes demandées
     * @return bool
     */
    private function widerThanAsked(array $suggestions, int $asked): bool
    {
        $columns = 0;
        foreach (array_unique(array_filter($suggestions, static fn (?string $sql): bool => $sql !== null)) as $sql) {
            $columns += count($this->suggested[$sql]['columns'] ?? []);
        }

        return $columns > $asked;
    }

    /**
     * Regroupe des suggestions SQL (une par ligne), null si aucune
     *
     * @param array<int, string|null> $suggestions Suggestions
     * @return string|null
     */
    private function joinSuggestions(array $suggestions): ?string
    {
        $suggestions = array_values(array_unique(array_filter($suggestions, static fn (?string $sql): bool => $sql !== null)));

        return ($suggestions === [] ? null : implode("\n", $suggestions));
    }

    /**
     * Libellé d'une table : « emprunts » ou « emprunts » (alias e)
     *
     * @param string $table Nom de la table
     * @param string $alias Alias de la table (minuscules)
     * @return string
     */
    private function tableLabel(string $table, string $alias): string
    {
        return '« ' . $table . ' »' . ($alias !== '' && $alias !== strtolower($table) ? ' (alias ' . $alias . ')' : '');
    }

    /**
     * Libellé de l'index utilisé par un accès
     *
     * @param array<string, mixed> $access Accès normalisé
     * @return string
     */
    private function keyLabel(array $access): string
    {
        return (isset($access['key']) === true && $access['key'] !== '' ? (string) $access['key'] : 'index');
    }

    /**
     * Libellé de colonnes préfixées par l'alias de la table : e.utilisateur_id, e.livre_id
     *
     * @param string $alias Alias de la table (minuscules)
     * @param array<int, string> $columns Colonnes
     * @return string
     */
    private function columnsLabel(string $alias, array $columns): string
    {
        $prefix = ($alias === '' || count($this->inspector->tableNames()) === 1 ? '' : $alias . '.');

        return implode(', ', array_map(static fn (string $column): string => $prefix . $column, $columns));
    }

    /**
     * Libellé du nombre de lignes : " (≈ 4 000 lignes)", vide si inconnu
     *
     * @param int|null $rows Nombre de lignes
     * @return string
     */
    private function rowsLabel(?int $rows): string
    {
        return ($rows === null ? '' : ' (≈ ' . $this->formatNumber($rows) . ' ligne' . ($rows > 1 ? 's' : '') . ')');
    }

    /**
     * Formate un nombre entier à la française (Exemple : 4 000)
     *
     * @param int $number Nombre
     * @return string
     */
    private function formatNumber(int $number): string
    {
        return number_format($number, 0, ',', "\u{202F}");
    }

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
    private function finding(string $level, string $title, string $detail, ?string $suggestion, ?string $table): array
    {
        return ['level' => $level, 'title' => $title, 'detail' => $detail, 'suggestion' => $suggestion, 'table' => $table];
    }
}
