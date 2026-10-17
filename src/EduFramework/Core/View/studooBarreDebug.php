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

use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Logs\HttpRequestLogger;

/**
 * Class studooBarreDebug
 * Barre de debug affichée en bas des pages en mode dev (style Web Debug Toolbar de Symfony) :
 * un bloc par information (statut HTTP, route, temps, mémoire, DEV),
 * le détail s'affiche au survol et la barre peut être réduite.
 *
 * @package Studoo\EduFramework\Core\View
 */
class studooBarreDebug
{
    use studooView;

    /**
     * @return string Retourne CSS global
     */
    public function generateCssGlobal(): string
    {
        return <<<'HTML'
<style>
#edu-tb, #edu-tb * { box-sizing: border-box; }
#edu-tb {
    position: fixed; left: 0; right: 0; bottom: 0; z-index: 99999; height: 36px;
    display: flex; justify-content: space-between;
    background: #222; color: #ddd; border-top: 1px solid #444;
    font: 12px/36px 'Open Sans', Helvetica, Arial, sans-serif; text-align: left;
}
#edu-tb a {
    color: inherit; text-decoration: none; display: block;
    margin: 0; padding: 0 12px; background: none; border: 0; border-radius: 0;
    font-size: inherit; line-height: inherit;
}
#edu-tb .edu-tb-group { display: flex; min-width: 0; }
#edu-tb .edu-tb-block { position: relative; padding: 0 12px; border-right: 1px solid #333; white-space: nowrap; cursor: default; }
#edu-tb .edu-tb-group.right .edu-tb-block { border-right: 0; border-left: 1px solid #333; }
#edu-tb .edu-tb-block:hover { background: #444; color: #fff; }
#edu-tb .edu-tb-block b { color: #fff; font-weight: 600; }
#edu-tb .edu-tb-block small { color: #999; font-size: 11px; margin-left: 3px; }
#edu-tb .edu-tb-status { color: #fff; font-weight: bold; background: #4f805d; }
#edu-tb .edu-tb-status.s3 { background: #3a7d8c; }
#edu-tb .edu-tb-status.s4 { background: #a46a1f; }
#edu-tb .edu-tb-status.s5 { background: #b0413e; }
#edu-tb .edu-tb-status:hover { filter: brightness(1.15); background: inherit; }
#edu-tb .edu-tb-env { background: #4f805d; color: #fff; padding: 1px 6px; border-radius: 3px; font-weight: bold; text-transform: uppercase; font-size: 11px; }
#edu-tb .edu-tb-env.prod { background: #b0413e; }
#edu-tb .edu-tb-ico { font-size: 18px; vertical-align: -1px; margin-right: 5px; }
#edu-tb .edu-tb-panel {
    display: none; position: absolute; bottom: 36px; left: 0; min-width: 260px; max-width: 480px;
    padding: 10px 14px; background: #444; color: #ddd; line-height: 1.5; white-space: normal;
    box-shadow: 0 -2px 8px rgba(0, 0, 0, .4); z-index: 1;
}
#edu-tb .edu-tb-group.right .edu-tb-panel { left: auto; right: 0; }
#edu-tb .edu-tb-block:hover .edu-tb-panel { display: block; }
#edu-tb .edu-tb-panel h4 { margin: 0 0 6px; font-weight: 600; font-size: 12px; line-height: 1.4; color: #fff; text-transform: uppercase; letter-spacing: .5px; }
#edu-tb .edu-tb-panel div { display: flex; justify-content: space-between; gap: 18px; }
#edu-tb .edu-tb-panel span { color: #aaa; }
#edu-tb .edu-tb-panel em { font-style: normal; color: #fff; word-break: break-all; text-align: right; }
#edu-tb .edu-tb-panel em a { color: #9fc6ff; }
#edu-tb .edu-tb-panel em a:hover { color: #c3d9ff; text-decoration: underline; }
#edu-tb .edu-tb-panel a { display: inline; padding: 0; margin: 0; }
#edu-tb .edu-tb-close { cursor: pointer; background: none; border: 0; color: #aaa; font: inherit; padding: 0 12px; }
#edu-tb .edu-tb-close:hover { color: #fff; background: #444; }
#edu-tb img { width: 16px; height: 16px; vertical-align: -3px; margin-right: 6px; }
#edu-tb-mini {
    display: none; position: fixed; right: 0; bottom: 0; z-index: 99999; height: 36px; padding: 0 10px;
    background: #222; border: 1px solid #444; border-right: 0; border-bottom: 0; border-radius: 4px 0 0 0; cursor: pointer;
    align-items: center;
}
#edu-tb-mini img { width: 18px; height: 18px; }
html.edu-tb-closed #edu-tb { display: none; }
html.edu-tb-closed #edu-tb-mini { display: flex; }
</style>
<script>
(function () {
    var key = 'edu-tb-closed', root = document.documentElement;
    try { if (localStorage.getItem(key) === '1') { root.classList.add('edu-tb-closed'); } } catch (e) {}
    window.eduToolbarToggle = function (close) {
        root.classList.toggle('edu-tb-closed', close);
        try { localStorage.setItem(key, close ? '1' : '0'); } catch (e) {}
    };
})();
</script>
HTML;
    }

    /**
     * @return string Retourne la barre de débogage
     */
    public function generateBarDebug(): string
    {
        $request = ConfigCore::getRequest();
        $status = http_response_code();
        $status = ($status === false ? 200 : $status);
        $controller = ($request->hasHander() === true ? $request->getHander() : null);
        $envName = (ConfigCore::existEnv('APP_ENV') === true ? (string) ConfigCore::getEnv('APP_ENV') : 'dev');
        $logo = $this->escape($this->logo());

        $elapsed = (microtime(true) - (float) ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))) * 1000;
        $peak = memory_get_peak_usage(true) / 1048576;

        $blocks = [
            $this->block(
                '<b>' . $status . '</b>',
                'Réponse HTTP',
                ['Statut' => $status . ' ' . $this->statusText($status), 'Méthode' => $request->getHttpMethod()],
                'edu-tb-status s' . intdiv($status, 100)
            ),
            $this->block(
                '<b>' . $this->escape($request->getHttpMethod()) . '</b> ' . $this->escape($request->getRoute()),
                'Route',
                [
                    'Chemin' => $request->getRoute(),
                    'Controller' => ($controller ?? 'aucun (page par défaut ou erreur)'),
                    'Paramètres' => ($request->getVars() === [] ? '—' : implode(', ', array_keys($request->getVars()))),
                ]
            ),
            $this->block(
                '<b>' . number_format($elapsed, 0, ',', ' ') . '</b><small>ms</small>',
                'Temps d\'exécution',
                ['Durée totale' => number_format($elapsed, 1, ',', ' ') . ' ms']
            ),
            $this->block(
                '<b>' . number_format($peak, 1, ',', ' ') . '</b><small>Mo</small>',
                'Mémoire',
                ['Pic d\'utilisation' => number_format($peak, 2, ',', ' ') . ' Mo', 'Limite PHP' => (string) ini_get('memory_limit')]
            ),
            $this->devToolBlock(),
        ];

        $right = [
            $this->block(
                '<span class="edu-tb-env' . ($envName === 'dev' ? '' : ' prod') . '">' . $this->escape($envName) . '</span>',
                'Environnement',
                array_merge(
                    ['APP_ENV' => $envName],
                    ['PHP' => PHP_VERSION, 'SAPI' => PHP_SAPI],
                    $this->databaseDetails(),
                    $this->devServicesDetails()
                )
            ),
            $this->block(
                '<img src="' . $logo . '" alt="">' . $this->escape((string) ConfigCore::getConfig('version')),
                'Edu Framework',
                [
                    'Version' => (string) ConfigCore::getConfig('version'),
                    'Livraison' => (string) ConfigCore::getConfig('date_version'),
                    'Documentation' => 'studoo-app.github.io/edu-framework',
                ],
                '',
                'https://studoo-app.github.io/edu-framework'
            ),
        ];

        return '<div id="edu-tb" role="complementary" aria-label="Edu Framework debug toolbar">'
            . '<div class="edu-tb-group">' . implode('', $blocks) . '</div>'
            . '<div class="edu-tb-group right">' . implode('', $right)
            . '<button type="button" class="edu-tb-close" title="Réduire la barre" onclick="eduToolbarToggle(true)">&times;</button>'
            . '</div></div>'
            . '<button type="button" id="edu-tb-mini" title="Afficher la barre de debug" aria-label="Afficher la barre de debug" onclick="eduToolbarToggle(false)">'
            . '<img src="' . $logo . '" alt=""></button>';
    }

    /**
     * Bloc DevTool de la barre : affiche le token de la requête en cours
     * et fait le lien vers son descriptif dans le profiler.
     * Si la requête n'est pas journalisée (logs indisponibles, route interne, ...),
     * le bloc pointe vers la liste des requêtes du profiler.
     *
     * @return string
     */
    private function devToolBlock(): string
    {
        $envName = (ConfigCore::existEnv('APP_ENV') === true ? (string) ConfigCore::getEnv('APP_ENV') : 'dev');
        $logId = HttpRequestLogger::getCurrentLogId();
        $label = '<span class="edu-tb-ico">&#9881;</span>';

        if ($logId !== null) {
            return $this->block(
                $label,
                'DEV TOOL',
                [
                    'Token' => '#' . $logId,
                    'Profiler' => 'Ouvrir le descriptif de la requête',
                ],
                '',
                '/_debug/profiler#request/' . $logId
            );
        }

        return $this->block(
            $label,
            'DEV',
            ['Profiler' => 'Ouvrir la liste des requêtes'],
            '',
            '/_debug/profiler#logs'
        );
    }

    /**
     * Détails sur la base de données (affichés dans le panneau de survol du bloc DEV, uniquement si elle est activée)
     *
     * @return array<string, string> Lignes du panneau (libellé => valeur)
     */
    private function databaseDetails(): array
    {
        if (ConfigCore::existEnv('DB_HOST_STATUS') === false || ConfigCore::getEnv('DB_HOST_STATUS') !== 'true') {
            return [];
        }

        $get = static fn (string $key): string => (ConfigCore::existEnv($key) === true ? (string) ConfigCore::getEnv($key) : '—');

        return [
            '---- database ----' => "--------",
            'DB_HOST_STATUS' => $get('DB_HOST_STATUS') ? "on" : "off",
            'DB_TYPE' => $get('DB_TYPE'),
            'DB_HOST' => $get('DB_HOST') . ':' . $get('DB_SOCKET'),
            'DB_NAME' => $get('DB_NAME'),
        ];
    }

    /**
     * Services de développement publiés sur l'hôte (affichés dans le panneau de survol du bloc DEV) :
     * application servie par FrankenPHP, base MariaDB, Mailpit, DbGate et phpMyAdmin.
     * Les URLs sont construites à partir du .env (mêmes variables que compose.yaml),
     * avec les valeurs par défaut du compose si une variable est absente.
     *
     * @return array<string, string|array{text: string, href: string}> Lignes du panneau (libellé => valeur ou lien)
     */
    private function devServicesDetails(): array
    {
        $env = static fn (string $key, string $default): string
            => (ConfigCore::existEnv($key) === true ? (string) ConfigCore::getEnv($key) : $default);
        $link = static fn (string $url): array => ['text' => $url, 'href' => $url];

        return [
            '---- services ----' => "--------",
            'Mailpit' => $link('http://localhost:' . $env('MAILER_UI_PORT', '8025')),
            'Mailpit SMTP' => 'localhost:' . $env('MAILER_SMTP_PORT', '1025'),
            'DbGate' => $link('http://localhost:' . $env('DBGATE_PORT', '8082')),
            'phpMyAdmin' => $link('http://localhost:' . $env('PMA_PORT', '8081')),
        ];
    }

    /**
     * Génère un bloc de la barre : contenu visible + panneau de détail au survol
     *
     * @param string $html Contenu HTML déjà échappé du bloc
     * @param string $title Titre du panneau de détail
     * @param array<string, string|array{text: string, href: string}> $details Lignes du panneau
     *        (libellé => valeur, ou libellé => lien sous la forme ['text' => ..., 'href' => ...]), échappées ici
     * @param string $class Classes CSS supplémentaires
     * @param string|null $href Lien (le bloc s'ouvre dans un nouvel onglet)
     */
    private function block(string $html, string $title, array $details, string $class = '', ?string $href = null): string
    {
        $rows = '';
        foreach ($details as $label => $value) {
            $content = is_array($value)
                ? '<a href="' . $this->escape($value['href']) . '" target="_blank" rel="noopener">'
                    . $this->escape($value['text']) . '</a>'
                : $this->escape($value);
            $rows .= '<div><span>' . $this->escape($label) . '</span><em>' . $content . '</em></div>';
        }

        $inner = $html . '<div class="edu-tb-panel"><h4>' . $this->escape($title) . '</h4>' . $rows . '</div>';
        $class = trim('edu-tb-block ' . $class);

        if ($href !== null) {
            return '<a class="' . $class . '" href="' . $this->escape($href) . '" target="_blank" rel="noopener">' . $inner . '</a>';
        }

        return '<div class="' . $class . '">' . $inner . '</div>';
    }

    private function statusText(int $status): string
    {
        return match ($status) {
            200 => 'OK',
            201 => 'Created',
            204 => 'No Content',
            301 => 'Moved Permanently',
            302 => 'Found',
            304 => 'Not Modified',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
            default => '',
        };
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
