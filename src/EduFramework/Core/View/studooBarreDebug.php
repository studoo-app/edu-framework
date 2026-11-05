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
use Studoo\EduFramework\Core\Logs\SqlQueryLogger;
use Studoo\EduFramework\Core\Profiler\CodeProfiler;

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
#edu-tb a, #edu-tb button {
    color: inherit; text-decoration: none; display: flex;
    margin: 0; padding: 0 12px; background: none; border: 0; border-radius: 0;
    font-size: inherit; line-height: 36px; font-family: inherit; height: 36px; align-items: center;
    cursor: pointer;
}
#edu-tb .edu-tb-group { display: flex; min-width: 0; }
#edu-tb .edu-tb-block { position: relative; padding: 0 12px; border-right: 1px solid #333; white-space: nowrap; cursor: default; }
#edu-tb .edu-tb-group.right .edu-tb-block { border-right: 0; border-left: 1px solid #333; }
#edu-tb .edu-tb-block:hover, #edu-tb .edu-tb-close:hover { background: #444; color: #fff; }
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
#edu-tb .edu-tb-block:hover .edu-tb-panel, #edu-tb .edu-tb-block:focus-within .edu-tb-panel { display: block; }
#edu-tb .edu-tb-panel h4 { margin: 0 0 6px; font-weight: 600; font-size: 12px; line-height: 1.4; color: #fff; text-transform: uppercase; letter-spacing: .5px; }
#edu-tb .edu-tb-panel div { display: flex; justify-content: space-between; gap: 18px; }
#edu-tb .edu-tb-panel span { color: #aaa; }
#edu-tb .edu-tb-panel em { font-style: normal; color: #fff; word-break: break-all; text-align: right; }
#edu-tb .edu-tb-panel em a { color: #9fc6ff; display: inline; line-height: 1.5; }
#edu-tb .edu-tb-panel em a:hover { color: #c3d9ff; text-decoration: underline; }
#edu-tb .edu-tb-panel a { display: inline; padding: 0; margin: 0; }
#edu-tb .edu-tb-close { 
    cursor: pointer; background: none; border: 0; border-left: 1px solid #333; 
    color: #aaa; font: inherit; padding: 0 12px; 
    appearance: none; -webkit-appearance: none; -moz-appearance: none;
}
#edu-tb .edu-tb-close:hover { color: #fff; background: #444; }
#edu-tb img { width: 16px; height: 16px; vertical-align: -3px; margin-right: 6px; }
#edu-tb-mini {
    display: none; position: fixed; right: 0; bottom: 0; z-index: 99999; height: 36px; padding: 0 10px;
    background: #222; border: 1px solid #444; border-right: 0; border-bottom: 0; border-radius: 4px 0 0 0; cursor: pointer;
    align-items: center;
}
#edu-tb-mini img { width: 18px; height: 18px; }
#edu-tb .edu-tb-code.off, #edu-tb .edu-tb-code.missing { color: #999; }
#edu-tb .edu-tb-code .edu-tb-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; border: 1px solid #999; margin-left: 6px; }
#edu-tb .edu-tb-code.on .edu-tb-dot { background: #4f805d; border-color: #4f805d; }
#edu-tb .edu-tb-code .edu-tb-code-label { margin-left: 6px; }
#edu-tb .edu-tb-code .edu-tb-panel { min-width: 440px; }
#edu-tb .edu-tb-panel .edu-tb-code-rows { display: block; }
#edu-tb .edu-tb-panel .edu-tb-code-rows span { flex-shrink: 0; }
#edu-tb .edu-tb-code > a { padding: 0; }
#edu-tb .edu-tb-panel a.edu-tb-flame {
    display: block; position: relative; height: auto; margin: 8px 0 2px; padding: 0;
    line-height: 0; background: #333; cursor: pointer;
}
#edu-tb .edu-tb-panel a.edu-tb-flame:hover { outline: 1px solid #9fc6ff; }
#edu-tb .edu-tb-panel .edu-tb-flame span { position: absolute; display: block; height: 9px; border-right: 1px solid #333; }
#edu-tb .edu-tb-panel .edu-tb-flame-caption { display: block; color: #999; font-size: 11px; }
#edu-tb .edu-tb-panel button.edu-tb-action {
    display: inline; height: auto; padding: 0; line-height: inherit;
    color: #9fc6ff; cursor: pointer; text-decoration: underline;
}
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
    // Profiler de code : le cookie edu_profile active le profilage des requêtes suivantes
    window.eduProfileToggle = function (on) {
        document.cookie = 'edu_profile=' + (on ? '1' : '0') + '; path=/; SameSite=Lax' + (on ? '' : '; max-age=0');
        // Le paramètre ?_profile=1 réactiverait le profilage : il est retiré de l'URL
        var url = new URL(window.location.href);
        if (on === false && url.searchParams.has('_profile')) {
            url.searchParams.delete('_profile');
            window.location.replace(url.href);
            return;
        }
        window.location.reload();
    };
    // Le profil est enregistré en fin de requête, parfois après l'affichage de la page :
    // le bloc Code le demande à la route JSON et réessaie tant qu'il n'est pas prêt
    window.eduLoadCodeProfile = function () {
        var block = document.getElementById('edu-tb-code');
        if (!block || !window.fetch) { return; }
        var tries = 0;
        (function load() {
            fetch('/edu-code-profile?token=' + encodeURIComponent(block.getAttribute('data-token')))
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (data.status === 'ready') { eduFillCodeBlock(block, data); return; }
                    if (++tries < 6) { setTimeout(load, 250 * tries); return; }
                    block.querySelector('.edu-tb-code-label').textContent = '?';
                })
                .catch(function () { block.querySelector('.edu-tb-code-label').textContent = '?'; });
        })();
    };
    window.eduFillCodeBlock = function (block, data) {
        var ms = function (value) { return String(Math.round(value * 10) / 10).replace('.', ','); };
        var label = block.querySelector('.edu-tb-code-label');
        label.textContent = '';
        var strong = document.createElement('b');
        strong.textContent = ms(data.summary.wall_ms);
        var unit = document.createElement('small');
        unit.textContent = 'ms';
        label.appendChild(strong);
        label.appendChild(unit);
        if (data.top.length > 0) {
            // Nom court : sans le namespace ni le dossier (Exemple : LoadCouchCore::run)
            var name = data.top[0].name.split(/[\\/]/).pop();
            label.appendChild(document.createTextNode(' ' + (name.length > 28 ? '…' + name.slice(-27) : name)));
        }
        var rows = block.querySelector('.edu-tb-code-rows');
        var row = function (title, value) {
            var line = document.createElement('div');
            var span = document.createElement('span');
            var em = document.createElement('em');
            span.textContent = title;
            em.textContent = value;
            line.appendChild(span);
            line.appendChild(em);
            rows.appendChild(line);
        };
        rows.textContent = '';
        row('Temps profilé', ms(data.summary.wall_ms) + ' ms');
        row('Échantillons', data.summary.samples + ' (1 toutes les ' + ms(data.summary.period_ms) + ' ms)');
        row('Fonctions vues', String(data.summary.functions_count));
        row('Classement', 'par temps propre');
        data.top.slice(0, 3).forEach(function (fn, index) {
            row('Top ' + (index + 1), fn.name + ' — ' + ms(fn.self_ms) + ' ms (' + ms(fn.self_pct) + ' %)');
        });
        eduLoadMiniFlame(block);
    };
    // Mini-flamegraph du panneau : appels de plus de 1 % du temps, 12 niveaux au plus ;
    // un clic ouvre le flamegraph complet dans le profiler
    window.eduLoadMiniFlame = function (block) {
        var token = block.getAttribute('data-token');
        fetch('/edu-code-profile?token=' + encodeURIComponent(token) + '&format=flame&min=0.01&depth=12')
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.status !== 'ready' || !data.tree || data.tree.value === 0) { return; }
                var ms = function (value) { return String(Math.round(value * 100) / 100).replace('.', ','); };
                var colors = { app: '#e0b93f', framework: '#5d9bd5', vendor: '#9b82c9', other: '#888' };
                var link = document.createElement('a');
                link.className = 'edu-tb-flame';
                link.href = '/_debug/profiler#code/' + token + '/flame';
                link.target = '_blank';
                link.rel = 'noopener';
                link.setAttribute('aria-label', 'Ouvrir le flamegraph de la requête dans le profiler');
                var total = data.tree.value;
                var depthMax = 0;
                (function walk(node, depth) {
                    if (depth > 0) {
                        var bar = document.createElement('span');
                        bar.style.left = (node.start * 100 / total) + '%';
                        bar.style.width = (node.value * 100 / total) + '%';
                        bar.style.top = ((depth - 1) * 10) + 'px';
                        bar.style.background = colors[node.origin] || colors.other;
                        bar.title = node.name + ' — ' + ms(node.ms) + ' ms (' + String(node.pct).replace('.', ',') + ' %)';
                        link.appendChild(bar);
                        depthMax = Math.max(depthMax, depth);
                    }
                    node.children.forEach(function (child) { walk(child, depth + 1); });
                })(data.tree, 0);
                link.style.height = (depthMax * 10) + 'px';
                var caption = document.createElement('small');
                caption.className = 'edu-tb-flame-caption';
                caption.textContent = 'Flamegraph — jaune : mon code, bleu : framework, violet : vendor. Cliquer pour l’ouvrir.';
                var rows = block.querySelector('.edu-tb-code-rows');
                rows.appendChild(link);
                rows.appendChild(caption);
            })
            .catch(function () {});
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
            $this->sqlBlock(),
            $this->codeBlock(),
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
            . '<button type="button" class="edu-tb-close" title="Réduire la barre" onclick="eduToolbarToggle(true)" style="display: flex; align-items: center; height: 36px;">&times;</button>'
            . '</div></div>'
            . '<button type="button" id="edu-tb-mini" title="Afficher la barre de debug" aria-label="Afficher la barre de debug" onclick="eduToolbarToggle(false)">'
            . '<img src="' . $logo . '" alt=""></button>'
            . (CodeProfiler::isActive() === true ? '<script>eduLoadCodeProfile();</script>' : '');
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
     * Bloc SQL de la barre : affiche le nombre de requêtes SQL exécutées
     * pendant la requête HTTP en cours et leur temps cumulé, avec le lien
     * vers la vue SQL du profiler. Le bloc est masqué s'il n'y a aucune
     * requête collectée (base de données inactive ou pas de requête).
     * Les requêtes exécutées après le rendu de la page ne sont pas comptées
     * dans ce bloc (elles le sont dans le profiler).
     *
     * @return string
     */
    private function sqlBlock(): string
    {
        $count = SqlQueryLogger::count();
        if ($count === 0) {
            return '';
        }

        $totalMs = SqlQueryLogger::totalDurationMs();

        $details = [
            'Nombre de requêtes' => (string) $count,
            'Temps SQL cumulé' => number_format($totalMs, 1, ',', ' ') . ' ms',
        ];
        $slowest = SqlQueryLogger::slowestQuery();
        if ($slowest !== null) {
            $details['Requête la plus lente'] = number_format($slowest['duration_ms'], 1, ',', ' ')
                . ' ms — ' . mb_strimwidth($slowest['query'], 0, 60, '…');
        }
        $details['Profiler'] = 'Voir les requêtes SQL de cette requête HTTP';

        $logId = HttpRequestLogger::getCurrentLogId();

        return $this->block(
            '<b>' . $count . '</b><small>req SQL</small> <b>' . number_format($totalMs, 1, ',', ' ') . '</b><small>ms</small>',
            'Requêtes SQL',
            $details,
            '',
            ($logId !== null ? '/_debug/profiler#sql/' . $logId : '/_debug/profiler#logs')
        );
    }

    /**
     * Bloc Code de la barre : profiler du code PHP exécuté (extension Excimer).
     * - extension absente : bloc grisé avec le lien vers la documentation ;
     * - profilage inactif (EDU_PROFILER=trigger) : un clic pose le cookie edu_profile
     *   et recharge la page, qui est alors profilée, comme les suivantes ;
     * - requête profilée : le résumé (temps, fonctions les plus coûteuses) est chargé
     *   en JavaScript après l'affichage de la page, car le profil n'est enregistré
     *   qu'en fin de requête ; un clic ouvre l'onglet Code du profiler.
     * Le bloc est masqué si le profiler est désactivé (EDU_PROFILER=off).
     *
     * @return string
     */
    private function codeBlock(): string
    {
        if (CodeProfiler::mode() === 'off') {
            return '';
        }

        $title = '<h4>Profiler de code</h4>';

        if (CodeProfiler::isAvailable() === false) {
            return $this->block(
                'Code',
                'Profiler de code',
                [
                    'Extension' => 'excimer non installée',
                    'Documentation' => 'Installer le profiler',
                ],
                'edu-tb-code missing',
                'https://studoo-app.github.io/edu-framework/boost/debug-bar.html'
            );
        }

        $logId = HttpRequestLogger::getCurrentLogId();

        if (CodeProfiler::isActive() === true && $logId !== null) {
            // Le bouton Désactiver est un vrai bouton, hors du lien vers le profiler
            // (accessible au clavier : le panneau s'ouvre aussi au focus)
            $stop = (CodeProfiler::mode() === 'trigger')
                ? '<div><span>Profilage</span><em><button type="button" class="edu-tb-action" '
                    . 'onclick="eduProfileToggle(false)">Désactiver</button></em></div>'
                : '';
            $href = '/_debug/profiler#code/' . $logId;

            return '<div class="edu-tb-block edu-tb-code on" id="edu-tb-code" data-token="' . $logId . '">'
                . '<a href="' . $href . '" target="_blank" rel="noopener">'
                . 'Code <span class="edu-tb-code-label">&hellip;</span></a>'
                . '<div class="edu-tb-panel">' . $title
                . '<div class="edu-tb-code-rows"><div><span>Profil</span><em>enregistrement en cours&hellip;</em></div></div>'
                . $stop
                . '<div><span>Profiler</span><em><a href="' . $href . '" target="_blank" rel="noopener">Ouvrir l\'onglet Code</a></em></div>'
                . '</div></div>';
        }

        // Profilage disponible mais inactif pour cette requête (mode trigger sans cookie)
        return '<button type="button" class="edu-tb-block edu-tb-code off" onclick="eduProfileToggle(true)">'
            . 'Code<span class="edu-tb-dot"></span>'
            . '<div class="edu-tb-panel">' . $title
            . '<div><span>Profilage</span><em>désactivé</em></div>'
            . '<div><span>Action</span><em>Cliquer pour profiler cette page et les suivantes</em></div>'
            . '</div></button>';
    }

    /**
     * Détails sur la base de données (affichés dans le panneau de survol du bloc DEV,
     * uniquement si elle est activée via DB_HOST_STATUS=true) : type, hôte (avec les
     * valeurs par défaut localhost:3306), nom de la base et liens vers DbGate et phpMyAdmin
     *
     * @return array<string, string|array{text: string, href: string}> Lignes du panneau (libellé => valeur ou lien)
     */
    private function databaseDetails(): array
    {
        if (ConfigCore::existEnv('DB_HOST_STATUS') === false || ConfigCore::getEnv('DB_HOST_STATUS') !== 'true') {
            return [
            '---- database ----' => "--------",
            'DB_HOST_STATUS' => "off",
            ];
        }

        $get = static fn (string $key): string => (ConfigCore::existEnv($key) === true ? (string) ConfigCore::getEnv($key) : '—');
        $env = static fn (string $key, string $default): string
            => (ConfigCore::existEnv($key) === true ? (string) ConfigCore::getEnv($key) : $default);
        $link = static fn (string $name, string $url): array => ['text' => $name, 'href' => $url];

        return [
            '---- database ----' => "--------",
            'DB_HOST_STATUS' => $get('DB_HOST_STATUS') ? "on" : "off",
            'DB_TYPE' => $get('DB_TYPE'),
            'DB_HOST' => $env('DB_HOST', 'localhost') . ':' . $env('DB_SOCKET', '3306'),
            'DB_NAME' => $get('DB_NAME'),
            'DbGate' => $link('localhost:' . $env('DBGATE_PORT', '8082') ,'http://localhost:' . $env('DBGATE_PORT', '8082')),
            'phpMyAdmin' => $link('localhost:' . $env('PMA_PORT', '8081'), 'http://localhost:' . $env('PMA_PORT', '8081')),
        ];
    }

    /**
     * Services de messagerie publiés sur l'hôte (affichés dans le panneau de survol du bloc DEV) :
     * interface web et SMTP de Mailpit. Les URLs sont construites à partir du .env
     * (mêmes variables que compose.yaml), avec les valeurs par défaut du compose
     * si une variable est absente.
     *
     * @return array<string, string|array{text: string, href: string}> Lignes du panneau (libellé => valeur ou lien)
     */
    private function devServicesDetails(): array
    {
        $env = static fn (string $key, string $default): string
            => (ConfigCore::existEnv($key) === true ? (string) ConfigCore::getEnv($key) : $default);
        $link = static fn (string $name, string $url): array => ['text' => $name, 'href' => $url];

        return [
            '---- smtp ----' => "--------",
            'Mailpit' => $link('localhost:' . $env('MAILER_UI_PORT', '8025'), 'http://localhost:' . $env('MAILER_UI_PORT', '8025')),
            'Mailpit SMTP' => 'localhost:' . $env('MAILER_SMTP_PORT', '1025')
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

    /**
     * Libellé du statut HTTP (Exemple : 404 => Not Found)
     *
     * @param int $status Code de statut HTTP
     * @return string Libellé du statut, chaine vide si inconnu
     */
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

    /**
     * Échappe une valeur pour un affichage HTML
     *
     * @param string $value Valeur à échapper
     * @return string Valeur échappée
     */
    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
