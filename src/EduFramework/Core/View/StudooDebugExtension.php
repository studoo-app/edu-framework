<?php
/*
 * Ce fichier fait partie du edu-framework.
 *
 * (c) Studoo
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */


namespace Studoo\EduFramework\Core\View;

use Twig\Extension\AbstractExtension;

class StudooDebugExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new \Twig\TwigFunction('dd', [$this, 'dd']),
        ];
    }

    public function dd($var): void
    {
        ob_start();
        var_dump($var);
        $result = ob_get_clean();

        echo "<div style=\"background-color: grey; color: white; padding: 10px; margin: 10px 0;\"><pre>{$result}</pre></div>";
    }
}