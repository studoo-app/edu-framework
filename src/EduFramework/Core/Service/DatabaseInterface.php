<?php

namespace Studoo\EduFramework\Core\Service;

use PDO;

interface DatabaseInterface
{

    /**
     * Permets de récupérer la connexion à la base de données
     * @return PDO
     */
    public function getManager(): PDO;
}
