<?php

namespace Core\Logs;

/**
 * Objet factice stocké en session dans les tests de HttpRequestLogger :
 * propriétés publique, privée et protégée, pour vérifier que normalize()
 * enregistre toutes les propriétés d'un objet
 */
class SessionUserFixture
{
    /**
     * Nom de l'utilisateur (propriété publique)
     * @var string
     */
    public string $nom = 'Ada';

    /**
     * @param string $email Email de l'utilisateur (propriété privée)
     * @param int $age Âge de l'utilisateur (propriété protégée)
     */
    public function __construct(
        private string $email = 'ada@studoo.app',
        protected int $age = 36
    ) {
    }

    /**
     * @return string Email de l'utilisateur
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return int Âge de l'utilisateur
     */
    public function getAge(): int
    {
        return $this->age;
    }
}
