<?php

/*
 * Edu Framework by studoo labs
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
 * Class TraceablePDOStatement
 * Statement PDO traçable : mesure le temps d'exécution de chaque appel à
 * execute() et transmet la requête SQL, avec les valeurs de ses paramètres,
 * au collecteur SqlQueryLogger, qui la rattache à la requête HTTP en cours.
 * Les valeurs des paramètres permettent au profiler de rejouer la requête
 * pour en afficher le plan d'exécution (EXPLAIN).
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
     * Paramètres liés par bindValue() : nom (Exemple : ":id") ou position (à partir de 1)
     * @var array<int|string, array{value: mixed, type: int}>
     */
    private array $boundValues = [];

    /**
     * Paramètres liés par référence par bindParam() : la valeur est lue au moment de execute()
     * @var array<int|string, array{ref: mixed, type: int}>
     */
    private array $boundParams = [];

    /**
     * Constructeur appelé par PDO à chaque prepare() ou query()
     * (PDO::ATTR_STATEMENT_CLASS est déclaré sans arguments de construction)
     */
    protected function __construct()
    {
    }

    /**
     * Lie une valeur à un paramètre et la mémorise pour le profiler
     *
     * @param int|string $param Nom (Exemple : ":id") ou position (à partir de 1) du paramètre
     * @param mixed $value Valeur du paramètre
     * @param int $type Type PDO du paramètre (PDO::PARAM_*)
     * @return bool
     */
    public function bindValue(int|string $param, mixed $value, int $type = PDO::PARAM_STR): bool
    {
        unset($this->boundParams[$param]);
        $this->boundValues[$param] = ['value' => $value, 'type' => $type];
        return parent::bindValue($param, $value, $type);
    }

    /**
     * Lie une variable (par référence) à un paramètre et la mémorise pour le profiler :
     * sa valeur est relevée à chaque execute()
     *
     * @param int|string $param Nom (Exemple : ":id") ou position (à partir de 1) du paramètre
     * @param mixed $var Variable liée par référence
     * @param int $type Type PDO du paramètre (PDO::PARAM_*)
     * @param int $maxLength Longueur maximale (paramètres de sortie)
     * @param mixed $driverOptions Options du pilote
     * @return bool
     */
    public function bindParam(
        int|string $param,
        mixed &$var,
        int $type = PDO::PARAM_STR,
        int $maxLength = 0,
        mixed $driverOptions = null
    ): bool {
        unset($this->boundValues[$param]);
        $this->boundParams[$param] = ['ref' => null, 'type' => $type];
        $this->boundParams[$param]['ref'] = &$var;
        return parent::bindParam($param, $var, $type, $maxLength, $driverOptions);
    }

    /**
     * Exécute le statement préparé et mesure son temps d'exécution
     *
     * @param array<int|string, mixed>|null $params Paramètres de la requête (Exemple : [':id' => 1])
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
            ($rows !== null && $rows >= 0 ? $rows : null),
            $this->capturedParams($params)
        );
        return $result;
    }

    /**
     * Retourne les paramètres utilisés par l'exécution : ceux passés à execute()
     * (ils remplacent alors les paramètres liés), sinon ceux liés par bindValue()
     * et bindParam(). Une position est numérotée à partir de 1, comme pour bindValue()
     *
     * @param array<int|string, mixed>|null $params Paramètres passés à execute()
     * @return array<int, array{name: int|string, value: mixed, type: int}>
     */
    private function capturedParams(?array $params): array
    {
        $captured = [];

        if ($params !== null) {
            foreach ($params as $name => $value) {
                $captured[] = [
                    'name' => (is_int($name) === true ? $name + 1 : $name),
                    'value' => $value,
                    'type' => PDO::PARAM_STR,
                ];
            }
            return $captured;
        }

        foreach ($this->boundValues as $name => $bound) {
            $captured[] = ['name' => $name, 'value' => $bound['value'], 'type' => $bound['type']];
        }
        foreach ($this->boundParams as $name => $bound) {
            $captured[] = ['name' => $name, 'value' => $bound['ref'], 'type' => $bound['type']];
        }

        return $captured;
    }
}
