<?php

/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Formatter;

/*
 * Class BufferFormat
 * Classe abstraite pour gérer le formatage des logs à partir du buffer.
 * 
 * @package Studoo\EduFramework\Core\Formatter
 * @property string $buffer Le tampon contenant les logs à formater.
 */
abstract class BufferFormat
{
    private string $buffer;

    public function __construct(string $buffer) {
        $this->buffer = $buffer;
    }

    /**
     * Récupère le buffer
     * 
     * @return string Le buffer contenant les logs.
     */
    public function getBuffer(): string {
        return $this->buffer;
    }
}

