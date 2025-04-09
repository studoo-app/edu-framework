<?php

/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\View;

/**
 * Trait studooView
 * @package Studoo\EduFramework\Core\View
 */
trait studooView
{
    /**
     * @return string Retourne le logo de l'application
     */
    public function logo(): string
    {
        return 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDI3LjEuMSwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkNhbHF1ZV8xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB4PSIwcHgiIHk9IjBweCIKCSB2aWV3Qm94PSIwIDAgNTI0IDUyMiIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgNTI0IDUyMjsiIHhtbDpzcGFjZT0icHJlc2VydmUiPgo8c3R5bGUgdHlwZT0idGV4dC9jc3MiPgoJLnN0MHtmaWxsOiNDNkIzOTM7fQoJLnN0MXtmaWxsOiM5OEVBRDA7fQo8L3N0eWxlPgo8Zz4KCTxyZWN0IHg9IjIzMyIgeT0iMjMzIiBjbGFzcz0ic3QwIiB3aWR0aD0iMjcwIiBoZWlnaHQ9IjI3MCIvPgo8L2c+CjxnPgoJPGc+CgkJPHJlY3QgeD0iMjIiIHk9IjIyIiBjbGFzcz0ic3QxIiB3aWR0aD0iNDgxIiBoZWlnaHQ9IjE1MiIvPgoJPC9nPgoJPGc+CgkJPHJlY3QgeD0iMjIiIHk9IjE3NCIgY2xhc3M9InN0MSIgd2lkdGg9IjE1MiIgaGVpZ2h0PSIzMjkiLz4KCTwvZz4KPC9nPgo8L3N2Zz4K';
    }
}
