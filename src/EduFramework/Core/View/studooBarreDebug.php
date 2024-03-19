<?php
/*
 * Ce fichier fait partie du edu-framework.
 *
 * (c) redbull
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\View;

use Studoo\EduFramework\Core\ConfigCore;

class studooBarreDebug
{
    public function generateCssGlobal(): string
    {
        return "<style>
                        .container-fluid {
                            min-height: 100vh!important;
                        }
                </style>";
    }


    public function generateStickyBar(): string
    {
        return '<div class="stickybar" style="
                                                display: flex; 
                                                position: sticky; 
                                                bottom: 0; 
                                                background-color: #000000;
                                                padding: 5px;
                                                text-align: center;
                                                color: white;
                                                 ">
                <div style="flex: 4;">
                    <div style="display: flex; justify-content: flex-start; align-items: center; ">
                        <span style="color: black; padding-right: 3px; padding-left: 3px; background: palegoldenrod; padding-left: 1px">' . ConfigCore::getRequest()->getHttpMethod() . '</span>
                        <span style="color: black; padding-right: 3px; padding-left: 3px; background: palegoldenrod; padding-left: 1px">' . ConfigCore::getRequest()->getRoute() . '</span>
                        <span style="color: white; margin-left: 10px"><a href="https://studoo-app.github.io/edu-framework-doc/" target="_blank" style="color: white;">Documentation</a></span>
                     </div>
                </div>
                <div style="flex: 8;"></div>
                <div style="flex: 4;">
                    <div style="display: flex; justify-content: flex-end; align-items: center;">
                        <img src="' . $this->logo() . '" width="20px">
                        <span style="padding-left: 5px;">' . ConfigCore::getConfig('version') . '</span>
                     </div>
                </div>
            </div>';
    }

    private function logo(): string
    {
        return 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDI3LjEuMSwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkNhbHF1ZV8xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB4PSIwcHgiIHk9IjBweCIKCSB2aWV3Qm94PSIwIDAgNTI0IDUyMiIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgNTI0IDUyMjsiIHhtbDpzcGFjZT0icHJlc2VydmUiPgo8c3R5bGUgdHlwZT0idGV4dC9jc3MiPgoJLnN0MHtmaWxsOiNDNkIzOTM7fQoJLnN0MXtmaWxsOiM5OEVBRDA7fQo8L3N0eWxlPgo8Zz4KCTxyZWN0IHg9IjIzMyIgeT0iMjMzIiBjbGFzcz0ic3QwIiB3aWR0aD0iMjcwIiBoZWlnaHQ9IjI3MCIvPgo8L2c+CjxnPgoJPGc+CgkJPHJlY3QgeD0iMjIiIHk9IjIyIiBjbGFzcz0ic3QxIiB3aWR0aD0iNDgxIiBoZWlnaHQ9IjE1MiIvPgoJPC9nPgoJPGc+CgkJPHJlY3QgeD0iMjIiIHk9IjE3NCIgY2xhc3M9InN0MSIgd2lkdGg9IjE1MiIgaGVpZ2h0PSIzMjkiLz4KCTwvZz4KPC9nPgo8L3N2Zz4K';
    }
}
