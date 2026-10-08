<?php

/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Controller;

use Studoo\EduFramework\Core\Exception\ErrorControllerException;

trait BuildControllerTrait
{
    /**
     * Permet de construire le controller
     * @param string $controller Nom du controller
     * @return ControllerInterface Retourne l'objet du controller
     * @throws ErrorControllerException
     */
    public function buildController(string $controller): ControllerInterface
    {
        // On vérifie que le controller existe et qu'il implémente l'interface ControllerInterface
        if (class_exists($controller) === false
            || in_array(ControllerInterface::class, class_implements($controller), true) === false) {
            throw new ErrorControllerException();
        }

        // Création de l'objet du controller en respectant le contrat de l'interface "ControllerInterface"
        return new $controller();
    }

    /**
     * Sépare le nom de la classe du controller et le nom de la méthode à appeler
     * Le handler peut être de deux formes :
     *     - "Controller\MedecinController" : la méthode execute() sera appelée par défaut
     *     - "Controller\MedecinController::index" : la méthode index() sera appelée
     * @param string $handler Nom du controller suivi ou non de la méthode à appeler
     * @return array<mixed> Tableau contenant le nom de la classe et le nom de la méthode [0 => classe, 1 => méthode|null]
     */
    public function resolveHandler(string $handler): array
    {
        // Pas de méthode explicite dans le handler, la méthode par défaut est execute()
        if (strpos($handler, '::') === false) {
            return [$handler, null];
        }

        // Séparation du nom de la classe et du nom de la méthode
        [$controller, $method] = explode('::', $handler, 2);

        // Si aucune méthode n'est renseignée, on retombe sur la méthode par défaut execute()
        if ($method === '') {
            return [$controller, null];
        }

        return [$controller, $method];
    }

    /**
     * Appelle la méthode du controller associée à la requête HTTP
     * Cette méthode est utilisée quand la route définit une méthode explicite Exemple: Controller\MedecinController::index
     * La méthode du controller doit être publique, non statique,
     * avoir un paramètre de type Request et retourner une chaine de caractères (string) ou null
     * @param Request $request La requête HTTP contenant le controller (getHander) et la méthode (getAction)
     * @return string|null
     * @throws ErrorControllerException
     */
    public function callAction(Request $request): string|null
    {
        $controller = $request->getHander();
        $action = $request->getAction();

        // On vérifie que le controller existe
        if (class_exists($controller) === false) {
            throw new ErrorControllerException("Le controller <$controller> n'existe pas");
        }

        // On vérifie que la méthode existe dans le controller
        if (method_exists($controller, $action) === false) {
            throw new ErrorControllerException("La méthode <$action> n'existe pas dans le controller <$controller>");
        }

        $reflection = new \ReflectionMethod($controller, $action);

        // On vérifie que la méthode est publique et non statique
        if ($reflection->isPublic() === false || $reflection->isStatic() === true) {
            throw new ErrorControllerException(
                "La méthode <$action> du controller <$controller> doit être publique et non statique"
            );
        }

        // On vérifie que la méthode a un paramètre de type Request
        $parameters = $reflection->getParameters();
        if (count($parameters) === 0
            || $parameters[0]->getType() === null
            || $parameters[0]->getType()->getName() !== Request::class) {
            throw new ErrorControllerException(
                "La méthode <$action> du controller <$controller> doit avoir un paramètre de type Request"
            );
        }

        // On vérifie que le type de retour est compatible avec le framework : string ou null
        $returnType = $reflection->getReturnType();
        if ($returnType !== null
            && ($returnType instanceof \ReflectionNamedType === false
                || in_array($returnType->getName(), ['string', 'mixed'], true) === false)) {
            throw new ErrorControllerException(
                "La méthode <$action> du controller <$controller> doit retourner une chaine de caractères (string) ou null"
            );
        }

        // Appel de la méthode du controller avec la requête HTTP
        return (new $controller())->$action($request);
    }
}
