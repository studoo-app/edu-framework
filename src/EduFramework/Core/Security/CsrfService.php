<?php

/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Security;

/**
 * Class CsrfService
 * Elle permet de protéger les formulaires contre les attaques CSRF
 * (Cross-Site Request Forgery) grâce à des jetons (tokens)
 * basés sur le pattern "Synchronizer Token"
 *
 * Le fonctionnement repose sur trois principes :
 *   - un jeton aléatoire est généré par le serveur et stocké en session
 *   - le formulaire transmet ce jeton dans un champ caché (_csrf_token)
 *   - le serveur vérifie que le jeton reçu correspond au jeton de la session
 *
 * Chaque jeton est associé à un nom de route (cloisonnement) :
 * le jeton du formulaire /medecin/import n'est pas accepté sur une autre route.
 * Le jeton est renouvelé (rotation) après chaque validation réussie,
 * ce qui limite la fenêtre de rejeu d'un jeton intercepté
 * @package Studoo\EduFramework\Core\Security
 */
class CsrfService
{
    /**
     * Nom du champ de formulaire qui transporte le jeton CSRF
     * Exemple: <input type="hidden" name="_csrf_token" value="...">
     */
    public const TOKEN_FIELD_NAME = '_csrf_token';

    /**
     * Nom de l'en-tête HTTP qui peut transporter le jeton CSRF (Exemple: un appel AJAX)
     */
    public const TOKEN_HEADER_NAME = 'X-CSRF-TOKEN';

    /**
     * Clé de la variable de session qui contient la liste des jetons par nom de route
     * Structure: $_SESSION['_csrf_tokens'][nomDeRoute] = jeton
     */
    private const SESSION_KEY = '_csrf_tokens';

    /**
     * Taille en octets du jeton généré (soit 64 caractères hexadécimaux)
     */
    private const TOKEN_BYTES = 32;

    /**
     * Génère et renvoie le jeton CSRF associé à un nom de route
     * Au premier appel, un jeton aléatoire est généré (CSPRNG : random_bytes)
     * et stocké en session. Les appels suivants renvoient le même jeton
     * tant qu'il n'a pas été validé
     * @param string $routeName Le nom de la route définie dans le fichier Config/routes.yaml
     *                        Exemple: medecin_import
     * @return string Le jeton CSRF en notation hexadécimale (64 caractères)
     */
    public static function getToken(string $routeName): string
    {
        self::startSession();

        $token = $_SESSION[self::SESSION_KEY][$routeName] ?? '';

        if ($token === '') {
            $token = bin2hex(random_bytes(self::TOKEN_BYTES));
            $_SESSION[self::SESSION_KEY][$routeName] = $token;
        }

        return $token;
    }

    /**
     * Valide le jeton CSRF reçu pour un nom de route
     * La comparaison utilise hash_equals() : elle est protégée
     * contre les attaques temporelles (timing attack)
     * En cas de validation réussie, le jeton est renouvelé (rotation) :
     * la prochaine soumission du formulaire devra présenter le nouveau jeton
     * @param string $routeName Le nom de la route définie dans le fichier Config/routes.yaml
     * @param string|null $token Le jeton reçu du formulaire ou de l'en-tête HTTP
     * @return bool true si le jeton est valide, false sinon
     */
    public static function validate(string $routeName, string|null $token): bool
    {
        self::startSession();

        $tokenSession = $_SESSION[self::SESSION_KEY][$routeName] ?? '';

        if ($token === null || $token === '' || $tokenSession === '') {
            return false;
        }

        if (hash_equals($tokenSession, $token) === true) {
            // Rotation du jeton : il est renouvelé après chaque validation réussie
            $_SESSION[self::SESSION_KEY][$routeName] = bin2hex(random_bytes(self::TOKEN_BYTES));
            return true;
        }

        return false;
    }

    /**
     * Renvoie le champ de formulaire caché contenant le jeton CSRF du nom de route
     * Ce champ est à insérer dans tous les formulaires POST
     * Exemple: <input type="hidden" name="_csrf_token" value="a1b2c3...">
     * @param string $routeName Le nom de la route définie dans le fichier Config/routes.yaml
     * @return string Le champ HTML caché (valeur échappée)
     */
    public static function getTokenField(string $routeName): string
    {
        return '<input type="hidden" name="' . self::TOKEN_FIELD_NAME . '" value="'
            . htmlspecialchars(self::getToken($routeName), ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Démarre la session PHP si elle n'est pas déjà active
     * Si la session ne peut pas être démarrée (Exemple: en ligne de commande,
     * des entêtes HTTP déjà envoyés pendant les tests unitaires),
     * la superglobale $_SESSION reste utilisable comme tableau en mémoire
     * @return void
     */
    private static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE && headers_sent() === false) {
            session_start();
        }
    }
}
