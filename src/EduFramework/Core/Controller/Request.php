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

/**
 * Class Request
 * Elle permet de définir une requête HTTP
 * On peut récupérer la méthode HTTP (GET, POST ...), la route, le controller et les variables de la requête HTTP
 */
class Request
{
    /**
     * La méthode HTTP de la requête
     * @var string $httpMethod
     */
    private string $httpMethod;

    /**
     * La route de la requête
     * @var string $route
     */
    private string $route;

    /**
     * Nom de la classe du controller
     * @var string $hander
     */
    private string $hander = '';

    /**
     * La méthode du controller à appeler
     * Par défaut, c'est la méthode execute() du controller
     * @var string $action
     */
    private string $action = 'execute';

    /**
     * Les variables de la requête HTTP
     * @var array<mixed> $vars
     */
    private array $vars = [];

    /**
     * Les fichiers téléversés de la requête HTTP (structure normalisée)
     * Chaque clé du formulaire pointe vers une liste de fichiers
     * Exemple: $files['fichier'][0]['name']
     * @var array<mixed> $files
     */
    private array $files = [];

    /**
     * Une requete HTTP a obligatoirement une route et une méthode HTTP (GET, POST ...)
     * Cet objet est présent dans la méthode execute() d'un controller
     * @param string $route La route de la requête
     * @param string $httpMethod La méthode HTTP de la requête
     */
    public function __construct(string $route, string $httpMethod)
    {
        $this->httpMethod = $httpMethod;
        $this->route = $route;
    }

    /**
     * Permet de récupérer les headers de la requête HTTP
     * @return bool|array<mixed>
     */
    public function getHearder(): bool|array
    {
        if (function_exists('getallheaders') === false) {
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) === 'HTTP_') {
                    $headers[str_replace(
                        ' ',
                        '-',
                        ucwords(strtolower(str_replace('_', ' ', substr($name, 5))))
                    )] = $value;
                }
            }
            return $headers;
        }
        return getallheaders();
    }

    /**
     * Renvoi le nom du controller qui est associé à la requête HTTP
     * @return string
     */
    public function getHander(): string
    {
        return $this->hander;
    }

    /**
     * Indique si un controller est associé à la requête HTTP
     * @return bool
     */
    public function hasHander(): bool
    {
        return $this->hander !== '';
    }

    /**
     * Permet de définir le nom et instancier le controller qui est associé à la requête HTTP
     * @param string $hander Le nom de la classe du controller
     * @return Request
     */
    public function setHander(string $hander): Request
    {
        $this->hander = $hander;
        return $this;
    }

    /**
     * Renvoi la méthode du controller qui est associée à la requête HTTP
     * Par défaut, c'est la méthode execute() du controller
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Permet de définir la méthode du controller à appeler
     * La méthode est définie dans le fichier de configuration des routes (Config/routes.yaml)
     * Exemple: Controller\MedecinController::index
     * @param string $action Le nom de la méthode du controller
     * @return Request
     */
    public function setAction(string $action): Request
    {
        $this->action = $action;
        return $this;
    }

    /**
     * Permet de récupérer une variable de la requête HTTP
     * @param string $key Le nom de la variable
     * @return string|null
     */
    public function get(string $key): string|null
    {
        return $this->vars[$key] ?? null;
    }

    /**
     * Renvoi les variables de la requête HTTP
     * @return array<mixed>
     */
    public function getVars(): array
    {
        return $this->vars;
    }

    /**
     * Permet de définir les variables de la requête HTTP
     * @param array<mixed> $vars Les variables de la requête HTTP
     * @return Request
     */
    public function setVars(array $vars): Request
    {
        $this->vars = array_merge($this->vars, $vars);
        return $this;
    }

    /**
     * Permet de définir les fichiers téléversés de la requête HTTP ($_FILES)
     * La structure native de $_FILES est normalisée :
     * chaque clé du formulaire devient une liste de fichiers,
     * que le champ HTML soit simple (<input type="file" name="fichier">)
     * ou multiple (<input type="file" name="fichiers[]">)
     * @param array<mixed> $files Les fichiers téléversés (Exemple: $_FILES)
     * @return Request
     */
    public function setFiles(array $files): Request
    {
        foreach ($files as $key => $file) {
            if (is_array($file['name'] ?? null) === true) {
                // Champ multi-fichiers Exemple: <input type="file" name="fichiers[]">
                $listeFichiers = [];
                foreach (array_keys($file['name']) as $index) {
                    $listeFichiers[] = [
                        'name' => $file['name'][$index],
                        'type' => $file['type'][$index],
                        'size' => $file['size'][$index],
                        'tmp_name' => $file['tmp_name'][$index],
                        'error' => $file['error'][$index],
                    ];
                }
                $this->files[$key] = $listeFichiers;
            } else {
                // Champ simple
                $this->files[$key] = [
                    [
                        'name' => $file['name'],
                        'type' => $file['type'],
                        'size' => $file['size'],
                        'tmp_name' => $file['tmp_name'],
                        'error' => $file['error'],
                    ],
                ];
            }
        }
        return $this;
    }

    /**
     * Renvoi l'ensemble des fichiers téléversés de la requête HTTP (structure normalisée)
     * @return array<mixed>
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * Renvoi la liste des fichiers téléversés pour un champ du formulaire
     * La structure native de $_FILES est normalisée :
     * la méthode retourne toujours une liste de fichiers, même pour un champ simple
     * @param string $key Le nom du champ du formulaire
     * @return array<mixed>|null La liste des fichiers ou null si le champ n'existe pas
     */
    public function getFile(string $key): array|null
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Indique si le formulaire a envoyé un fichier pour ce champ
     * Attention : un champ laissé vide génère une entrée avec error = UPLOAD_ERR_NO_FILE (4)
     * hasFile() retourne true mais isValid() retourne false
     * @param string $key Le nom du champ du formulaire
     * @return bool
     */
    public function hasFile(string $key): bool
    {
        return array_key_exists($key, $this->files);
    }

    /**
     * Vérifie que le(s) fichier(s) téléversé(s) du champ sont valides (error = UPLOAD_ERR_OK)
     * @param string $key Le nom du champ du formulaire
     * @param int|null $index L'index du fichier à vérifier (champ multi-fichiers),
     *                        null pour vérifier tous les fichiers du champ
     * @return bool
     */
    public function isValid(string $key, int|null $index = null): bool
    {
        $fichiers = $this->getFile($key);

        if ($fichiers === null) {
            return false;
        }

        if ($index === null) {
            foreach ($fichiers as $fichier) {
                if ($fichier['error'] !== UPLOAD_ERR_OK) {
                    return false;
                }
            }
            return true;
        }

        return ($fichiers[$index]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
    }

    /**
     * Renvoi l'extension du fichier téléversé (en minuscules)
     * @param string $key Le nom du champ du formulaire
     * @param int $index L'index du fichier (champ multi-fichiers)
     * @return string|null L'extension (Exemple: csv) ou null
     */
    public function getExtension(string $key, int $index = 0): string|null
    {
        $fichier = $this->getFile($key)[$index] ?? null;

        if ($fichier === null || $fichier['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $extension = pathinfo($fichier['name'], PATHINFO_EXTENSION);

        return ($extension !== '' ? strtolower($extension) : null);
    }

    /**
     * Déplace un fichier téléversé vers sa destination finale
     * Cette méthode encapsule la fonction PHP move_uploaded_file()
     * en vérifiant au préalable que le téléversement est valide
     * @param string $key Le nom du champ du formulaire
     * @param string $destination Le chemin complet du fichier de destination
     * @param int $index L'index du fichier (champ multi-fichiers)
     * @return bool true si le fichier a été déplacé, false sinon
     */
    public function move(string $key, string $destination, int $index = 0): bool
    {
        $fichier = $this->getFile($key)[$index] ?? null;

        if ($fichier === null || $fichier['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        if (is_uploaded_file($fichier['tmp_name']) === false) {
            return false;
        }

        return move_uploaded_file($fichier['tmp_name'], $destination);
    }

    /**
     * Renvoi la méthode HTTP de la requête
     * @return string
     */
    public function getHttpMethod(): string
    {
        return $this->httpMethod;
    }

    /**
     * Permet de définir la méthode HTTP de la requête
     * @param string $httpMethod La méthode HTTP de la requête
     * @return Request
     */
    public function setHttpMethod(string $httpMethod): Request
    {
        $this->httpMethod = $httpMethod;
        return $this;
    }

    /**
     * Renvoi la route de la requête
     * @return string
     */
    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * Permet de définir la route de la requête
     * @param string $route La route de la requête
     * @return Request
     */
    public function setRoute(string $route): Request
    {
        $this->route = $route;
        return $this;
    }

    /**
     * Permet de récupérer le body de la requête HTTP
     * @return string
     */
    public function getBody(): false|string
    {
        return file_get_contents('php://input');
    }
}
