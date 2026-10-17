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

use PDOStatement;
use Studoo\EduFramework\Core\Logs\SqlQueryLogger;

/**
 * Class TraceablePDOStatement
 * Statement PDO traçable : mesure le temps d'exécution de chaque appel à
 * execute() et transmet la requête SQL au collecteur SqlQueryLogger, qui la
 * rattache à la requête HTTP en cours.
 *
 * Cette classe est instanciée par PDO lui-même (PDO::ATTR_STATEMENT_CLASS,
 * positionné par TraceablePDO) : son constructeur ne doit jamais être appelé
 * manuellement. Elle n'est disponible qu'en mode développement (APP_ENV=dev) :
 * la production utilise des statements PDO natifs, sans aucune mesure.
 *
 * @package Studoo\EduFramework\Core\Service
 */
class TraceablePDOStatement extends PDOStatement
{
    /**
     * Constructeur appelé par PDO à chaque prepare() ou query()
     * (PDO::ATTR_STATEMENT_CLASS est déclaré sans arguments de construction)
     */
    protected function __construct()
    {
    }

    /**
     * Exécute le statement préparé et mesure son temps d'exécution
     *
     * @param array<string, mixed>|null $params Paramètres de la requête (Exemple : [':id' => 1])
     * @return bool
     */
    public function execute(?array $params = null): bool
    {
        $start = microtime(true);
        $result = parent::execute($params);
        $rows = ($result === true ? $this->rowCount() : null);
        SqlQueryLogger::collect(
            (string) $this->queryString,
            round((microtime(true) - $start) * 1000, 1),
            // @phpstan-ignore greaterOrEqual.alwaysTrue (le pilote pgsql retourne -1 pour un SELECT)
            ($rows !== null && $rows >= 0 ? $rows : null)
        );
        return $result;
    }
}
