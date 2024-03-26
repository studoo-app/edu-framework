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
    use studooView;

    /**
     * @return string Retourne CSS global
     */
    public function generateCssGlobal(): string
    {
        return "<style>
                        .container-fluid {
                            min-height: 100vh!important;
                        }
                </style>";
    }

    /**
     * @return string Retourne la barre de débogage
     */
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
}
