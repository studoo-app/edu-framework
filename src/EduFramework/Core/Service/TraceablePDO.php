<?php

/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Service;

use PDO;
use PDOStatement;
use Studoo\EduFramework\Core\Logs\SqlQueryLogger;

/**
 * Class TraceablePDO
 * Connexion PDO traçable : mesure le temps d'exécution des requêtes SQL
 * (query() et exec()) et le transmet au collecteur SqlQueryLogger, qui les
 * rattache à la requête HTTP en cours. Les statements préparés sont tracés
 * à chaque execute() via TraceablePDOStatement (PDO::ATTR_STATEMENT_CLASS).
 *
 * Cette classe étend PDO : elle est interchangeable avec une connexion PDO
 * classique (DatabaseService::getConnect() reste typé PDO). Elle n'est
 * instanciée que par PdoFactory, uniquement en mode développement
 * (APP_ENV=dev avec l'extension pdo_sqlite) : la production utilise des
 * connexions PDO natives, sans aucune mesure ni collecte.
 *
 * @package Studoo\EduFramework\Core\Service
 */
class TraceablePDO extends PDO
{
    /**
     * TraceablePDO constructor.
     * Les statements préparés et les résultats de query() sont instanciés
     * via TraceablePDOStatement afin de mesurer chaque execute()
     *
     * @param string $dsn DSN de connexion (Exemple : mysql:host=...)
     * @param string|null $username Nom d'utilisateur
     * @param string|null $password Mot de passe
     * @param array<int|string, mixed>|null $options Options du pilote PDO
     */
    public function __construct(
        string $dsn,
        ?string $username = null,
        ?string $password = null,
        ?array $options = null
    ) {
        parent::__construct($dsn, $username, $password, $options);
        // Les statements retournés par prepare() et query() sont tracés à l'exécution
        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, [TraceablePDOStatement::class]);
    }

    /**
     * Exécute une requête SQL et mesure son temps d'exécution
     *
     * @param string $query Requête SQL à exécuter
     * @param mixed ...$fetchModeArgs Mode de récupération des résultats (PDO::FETCH_*)
     *                              suivi de ses éventuels arguments
     * @return PDOStatement|false
     */
    public function query(string $query, mixed ...$fetchModeArgs): PDOStatement|false
    {
        $start = microtime(true);
        $statement = parent::query($query, ...$fetchModeArgs);
        SqlQueryLogger::collect($query, self::elapsedMs($start), self::rowCountOf($statement));
        return $statement;
    }

    /**
     * Exécute une requête SQL et retourne le nombre de lignes affectées,
     * en mesurant son temps d'exécution
     *
     * @param string $statement Requête SQL à exécuter
     * @return int|false
     */
    public function exec(string $statement): int|false
    {
        $start = microtime(true);
        $rows = parent::exec($statement);
        SqlQueryLogger::collect($statement, self::elapsedMs($start), is_int($rows) ? $rows : null);
        return $rows;
    }

    /**
     * Retourne le temps écoulé depuis $start, en millisecondes arrondi à 1 décimale
     *
     * @param float $start Timestamp microtime(true) du début de la mesure
     * @return float
     */
    private static function elapsedMs(float $start): float
    {
        return round((microtime(true) - $start) * 1000, 1);
    }

    /**
     * Retourne le nombre de lignes du résultat, null si indisponible
     * (requête en échec ou pilote ne fournissant pas cette information,
     * Exemple : -1 pour un SELECT sous PostgreSQL)
     *
     * @param PDOStatement|false $statement Statement exécuté
     * @return int|null
     */
    private static function rowCountOf(PDOStatement|false $statement): int|null
    {
        if ($statement === false) {
            return null;
        }

        $rows = $statement->rowCount();

        // @phpstan-ignore greaterOrEqual.alwaysTrue (le pilote pgsql retourne -1 pour un SELECT)
        return ($rows >= 0 ? $rows : null);
    }
}
