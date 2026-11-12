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
 * Class SqlText
 * Texte d'une requête SQL préparé pour sa lecture par expressions régulières :
 * commentaires et guillemets d'identifiants (`table`) retirés, chaînes de
 * caractères remplacées par '' et profondeur de parenthèses de chaque caractère.
 * Permet d'extraire le contenu d'une clause (FROM, WHERE, ORDER BY, ...) en
 * tenant compte des sous-requêtes.
 *
 * @package Studoo\EduFramework\Core\Profiler\Sql
 */
class SqlText
{
    /**
     * Chaîne de caractères, commentaire ou guillemet d'identifiant
     */
    private const string TOKENS = "/'(?:[^'\\\\]|\\\\.|'')*'|--[^\\n]*|#[^\\n]*|\\/\\*.*?(?:\\*\\/|$)|`/s";

    /**
     * Requête d'origine
     */
    private string $sql;

    /**
     * Requête sans commentaires ni guillemets d'identifiants (les chaînes sont conservées)
     */
    private string $withoutComments;

    /**
     * Requête sur une ligne, sans commentaires, les chaînes remplacées par ''
     */
    private string $clean;

    /**
     * Profondeur de parenthèses de chaque caractère de la requête nettoyée
     * @var array<int, int>
     */
    private array $depths = [];

    /**
     * SqlText constructor.
     *
     * @param string $sql Requête SQL
     */
    public function __construct(string $sql)
    {
        $this->sql = $sql;
        $this->withoutComments = (string) preg_replace_callback(
            self::TOKENS,
            static fn (array $match): string => match ($match[0][0]) {
                "'" => $match[0],
                '`' => '',
                default => ' ',
            },
            $sql
        );
        $this->clean = (string) preg_replace(
            ['/' . "'(?:[^'\\\\]|\\\\.|'')*'" . '/s', '/\s+/'],
            ["''", ' '],
            $this->withoutComments
        );

        $depth = 0;
        foreach (str_split($this->clean) as $position => $char) {
            $depth += ($char === '(' ? 1 : 0);
            $this->depths[$position] = $depth;
            $depth = max(0, $depth - ($char === ')' ? 1 : 0));
        }
    }

    /**
     * Retourne la requête d'origine
     *
     * @return string
     */
    public function getSql(): string
    {
        return $this->sql;
    }

    /**
     * Retourne la requête sur une ligne, sans commentaires, les chaînes remplacées par ''
     *
     * @return string
     */
    public function getClean(): string
    {
        return $this->clean;
    }

    /**
     * Retourne la requête sur une ligne, sans commentaires, chaînes conservées
     *
     * @return string
     */
    public function getWithoutComments(): string
    {
        return (string) preg_replace('/\s+/', ' ', $this->withoutComments);
    }

    /**
     * Retourne le contenu de chaque clause ouverte par un mot-clé : du mot-clé jusqu'au
     * prochain mot-clé de fin au même niveau de parenthèses, ou jusqu'à la parenthèse
     * fermante qui termine la sous-requête
     *
     * @param string $keyword Expression régulière du mot-clé d'ouverture (Exemple : WHERE)
     * @param string $terminators Expression régulière des mots-clés de fin
     * @param bool $topLevelOnly Ne retourner que les clauses de la requête principale
     * @return array<int, string>
     */
    public function clauseBodies(string $keyword, string $terminators, bool $topLevelOnly = false): array
    {
        $bodies = [];
        preg_match_all('/\b(?:' . $keyword . ')\b/i', $this->clean, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as [$text, $offset]) {
            $depth = $this->depths[$offset] ?? 0;
            if ($topLevelOnly === true && $depth !== $this->topDepth()) {
                continue;
            }
            $start = $offset + strlen($text);
            $end = $this->clauseEnd($start, $depth, $terminators);
            $bodies[] = trim(substr($this->clean, $start, $end - $start));
        }

        return $bodies;
    }

    /**
     * Retire le contenu des parenthèses imbriquées d'un fragment de requête
     * (sous-requêtes, arguments de fonctions) : "a IN (SELECT ...)" devient "a IN ()"
     *
     * @param string $fragment Fragment de requête
     * @return string
     */
    public static function dropNested(string $fragment): string
    {
        $result = '';
        $depth = 0;
        foreach (str_split($fragment) as $char) {
            $opening = ($char === '(');
            $depth = max(0, $depth + ($opening === true ? 1 : 0) - ($char === ')' ? 1 : 0));
            $visible = ($depth === 0 || ($opening === true && $depth === 1));
            $result .= ($visible === true ? $char : '');
        }

        return $result;
    }

    /**
     * Découpe un fragment de requête sur les virgules qui ne sont pas entre parenthèses
     *
     * @param string $fragment Fragment de requête
     * @return array<int, string>
     */
    public static function splitTopLevel(string $fragment): array
    {
        $parts = [''];
        $depth = 0;
        foreach (str_split($fragment) as $char) {
            $depth = max(0, $depth + ($char === '(' ? 1 : 0) - ($char === ')' ? 1 : 0));
            if ($char === ',' && $depth === 0) {
                $parts[] = '';
                continue;
            }
            $parts[count($parts) - 1] .= $char;
        }

        return array_values(array_filter(array_map('trim', $parts), static fn (string $part): bool => $part !== ''));
    }

    /**
     * Retourne la position de fin d'une clause : premier mot-clé de fin au même niveau
     * de parenthèses, ou parenthèse fermante de la sous-requête, ou fin de la requête
     *
     * @param int $start Position du début du contenu de la clause
     * @param int $depth Profondeur de parenthèses du mot-clé d'ouverture
     * @param string $terminators Expression régulière des mots-clés de fin
     * @return int
     */
    private function clauseEnd(int $start, int $depth, string $terminators): int
    {
        $end = strlen($this->clean);
        preg_match_all('/\b(?:' . $terminators . ')\b/i', $this->clean, $matches, PREG_OFFSET_CAPTURE, $start);
        foreach ($matches[0] as [, $offset]) {
            if (($this->depths[$offset] ?? 0) === $depth) {
                $end = $offset;
                break;
            }
        }

        $closing = $start;
        while ($closing < $end) {
            if ($this->clean[$closing] === ')' && ($this->depths[$closing] ?? 0) <= $depth) {
                return $closing;
            }
            $closing++;
        }

        return $end;
    }

    /**
     * Retourne la profondeur de parenthèses de la requête principale
     * (0, ou 1 si toute la requête est entre parenthèses)
     *
     * @return int
     */
    private function topDepth(): int
    {
        return (preg_match('/^\s*\(/', $this->clean) === 1 ? 1 : 0);
    }
}
