<?php

namespace Studoo\EduFramework\Core\Formatter;

/*
 * Class BufferToServer
 * Classe pour formater le buffer des logs du serveur.
 * @package Studoo\EduFramework\Core\Formatter
 * @property string $buffer Le buffer contenant les logs à formater.
 */
class BufferToServer extends BufferFormat
{
    public function __construct(string $buffer)
    {
        parent::__construct($buffer);
    }

    /**
     * Formate le buffer pour la traitement des logs
     * Exemple de formatage : [Mon May  5 08:02:06 2025] 127.0.0.1:65229 [200]: GET /
     *
     * @return array
     */
    public function getFormatBuffer(): array
    {
        preg_match('/\[(.*?)\] (\d+\.\d+\.\d+\.\d+:\d+) \[(\d+)\]: (\w+) (.+)/', self::getBuffer(), $matches);

        return [
                'raw' => (is_array($matches) && isset($matches[0]) ? $matches[0] : self::getBuffer()),
                'timestamp' => (is_array($matches) && isset($matches[1]) ? $matches[1] : null),
                'ip_port' => (is_array($matches) && isset($matches[2]) ? $matches[2] : null),
                'status_code' => (is_array($matches) && isset($matches[3]) ? $matches[3] : null),
                'method' => (is_array($matches) && isset($matches[4]) ? $matches[4] : null),
                'path' => (is_array($matches) && isset($matches[5]) ? $matches[5] : null),
            ];
    }
}