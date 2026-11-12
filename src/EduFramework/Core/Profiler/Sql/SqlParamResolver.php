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

use PDO;

/**
 * Class SqlParamResolver
 * Associe à chaque paramètre d'une requête SQL (:nom ou ?) la valeur utilisée
 * pour l'analyse EXPLAIN : celle saisie dans le profiler, sinon celle enregistrée
 * à l'exécution de la requête. Un paramètre sans valeur est signalé (provided à
 * false) : la requête ne peut pas être analysée tant qu'il n'est pas renseigné.
 *
 * @package Studoo\EduFramework\Core\Profiler\Sql
 */
class SqlParamResolver
{
    /**
     * Retourne les paramètres de la requête avec leur valeur
     *
     * @param array<int, array{name: int|string, value: mixed, type: int}> $captured Paramètres enregistrés
     * @param array<int, int|string> $placeholders Paramètres présents dans la requête
     * @param string|null $override Valeurs saisies, objet JSON (Exemple : {":id": "3"})
     * @return array<int, array{name: int|string, value: mixed, type: int, provided: bool}>
     */
    public static function resolve(array $captured, array $placeholders, ?string $override): array
    {
        $known = self::byName($captured);
        $values = json_decode((string) $override, true);
        $values = (is_array($values) === true ? $values : []);

        return array_map(
            static fn (int|string $name): array => self::resolveOne($name, $known[(string) $name] ?? null, $values),
            $placeholders
        );
    }

    /**
     * Indexe les paramètres enregistrés par nom (":nom", préfixe ajouté si absent) ou position
     *
     * @param array<int, array{name: int|string, value: mixed, type: int}> $captured Paramètres enregistrés
     * @return array<string, array{name: int|string, value: mixed, type: int}>
     */
    private static function byName(array $captured): array
    {
        $known = [];
        foreach ($captured as $param) {
            $name = (string) $param['name'];
            $named = (is_string($param['name']) === true && str_starts_with($name, ':') === false);
            $known[$named === true ? ':' . $name : $name] = $param;
        }

        return $known;
    }

    /**
     * Retourne la valeur d'un paramètre : saisie, sinon enregistrée, sinon inconnue
     *
     * @param int|string $name Nom ou position du paramètre
     * @param array{name: int|string, value: mixed, type: int}|null $known Paramètre enregistré
     * @param array<int|string, mixed> $values Valeurs saisies
     * @return array{name: int|string, value: mixed, type: int, provided: bool}
     */
    private static function resolveOne(int|string $name, ?array $known, array $values): array
    {
        $type = (int) ($known['type'] ?? PDO::PARAM_STR);
        $entered = $values[(string) $name] ?? null;
        if (array_key_exists((string) $name, $values) === true && ($entered === null || is_scalar($entered) === true)) {
            return ['name' => $name, 'value' => self::cast($entered, $known), 'type' => $type, 'provided' => true];
        }

        return ['name' => $name, 'value' => $known['value'] ?? null, 'type' => $type, 'provided' => $known !== null];
    }

    /**
     * Convertit en entier une valeur saisie numérique si le paramètre enregistré était
     * un entier (ou si aucune valeur n'avait été enregistrée)
     *
     * @param mixed $value Valeur saisie
     * @param array{name: int|string, value: mixed, type: int}|null $known Paramètre enregistré
     * @return mixed
     */
    private static function cast(mixed $value, ?array $known): mixed
    {
        $integer = ($known === null || is_int($known['value']) === true || $known['type'] === PDO::PARAM_INT);
        if ($integer === true && is_string($value) === true && preg_match('/^-?\d{1,18}$/', $value) === 1) {
            return (int) $value;
        }

        return $value;
    }
}
