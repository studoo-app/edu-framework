# CHANGELOG

**Présentation des versions du framework Edu Framework**

## v2.8.0 - 20/10/2026

**new features**

- Profiler de code dans la barre de debug : le temps passé dans chaque fonction PHP d'une requête HTTP est mesuré par l'extension [Excimer](https://www.mediawiki.org/wiki/Excimer) (profiler par échantillonnage toutes les 0,1 ms, compatible FrankenPHP en mode classique et worker). Le profilage est **à la demande** (`EDU_PROFILER=trigger` par défaut) : le nouveau bloc **Code** de la barre de debug pose le cookie `edu_profile=1` (ou paramètre `?_profile=1`) et reste actif jusqu'au clic sur **Désactiver** ; `EDU_PROFILER=on` profile chaque requête, `off` désactive le profiler. Le profil est rattaché au token de la requête et stocké compressé dans la table `serv_code_profiles` (`EDU_PROFILER_KEEP` profils conservés, 50 par défaut). Le bloc affiche le temps profilé et le top 3 des fonctions ; le nouvel onglet **Code** du profiler (`/_debug/profiler#code/{token}`) liste toutes les fonctions (temps propre / inclusif, filtre par origine `app` / `framework` / `vendor`), leurs appelants et appelés, et exporte le profil au format Speedscope pour un flamegraph. Nouvel endpoint interne `GET /edu-code-profile` (mode dev uniquement). L'image Docker installe Excimer (argument de build `EDU_PROFILER`, `EDU_PROFILER_BUILD=0` pour l'exclure) et `php bin/edu check:config` signale l'extension comme recommandée
- Profiler : la section de résumé d'une requête n'affiche plus que l'URL ; les informations détaillées (méthode, statut HTTP, IP, SAPI, durée, mémoire, date, token) ont été retirées de l'en-tête pour alléger l'affichage

**documentation**

- new [Les fonctionnalités](docs/features.md) : toutes les fonctionnalités du framework regroupées par thème d'apprentissage, avec la notion travaillée et son équivalent dans Symfony
- new [Migrer de la version 2.3 à la version 2.8](docs/migrate/migration-2_3-2_8.md) : étapes de migration (sauvegarde et passage de MySQL à MariaDB, templates d'erreur, cache Twig, variables du `.env`, fichiers Docker), changements et nouveautés des versions 2.4 à 2.8
- update [La barre de debug et le profiler](docs/boost/debug-bar.md) : nouvelle section « Le profiler de code » (installation, modes, lecture du profil avec captures d'écran, lecture du flamegraph avec Speedscope, API)
- update [Installation avec Docker](docs/installation/docker.md) : variables d'environnement `EDU_PROFILER`, `EDU_PROFILER_KEEP` et `EDU_PROFILER_BUILD`
- update [Prérequis](docs/installation/prerequis.md) : installation de l'extension optionnelle `excimer` hors Docker

## v2.7.3 - 17/10/2026

**new features**

- Profiler : la vue Recherche (`/_debug/profiler#search`) gagne les **mêmes onglets de filtrage par famille de statut que la vue des logs** — `Tous / 2xx / 3xx / 4xx / 5xx`, avec le nombre de requêtes par famille en badge. Un clic filtre les résultats sur la famille sélectionnée (l'onglet `Tous` affiche toutes les requêtes), l'onglet actif est mis en évidence et le champ **Statut** du formulaire de recherche est synchronisé pour refléter le filtre appliqué. Les compteurs proviennent de l'API interne `/edu-logs` (`data.counts`, calculés côté serveur) : ils couvrent l'ensemble des requêtes correspondant aux autres filtres (URL, IP, dates, ...) et restent indépendants du filtre de statut, exactement comme les onglets de la vue des logs. Le rendu des badges est partagé entre les deux vues (fonction `renderCountBadges`) afin de garantir un design et un comportement identiques ; le tableau des résultats de la recherche reste inchangé

**bug Fixes**

- fix(toolbar): correction de l'alignement des colonnes contenant des liens (DbGate, phpMyAdmin, Mailpit) dans le panneau de la barre de debug en ajustant le style CSS des éléments `<em>` et des liens

**documentation**

- update [La barre de debug et le profiler](docs/boost/debug-bar.md) : les onglets de filtrage par famille de statut sont documentés pour les vues **Logs** et **Recherche** (clic pour filtrer, onglet actif, synchronisation du champ Statut du formulaire)

## v2.7.2 - 17/10/2026

**bug Fixes**

- fix(toolbar): correction finale de l'alignement du bouton de fermeture à droite de la barre de debug (styles flex, hauteur et alignement)

## v2.7.1 - 17/10/2026

**bug Fixes**

- fix(toolbar): correction du décalage du bouton de fermeture à droite de la barre de debug, amélioration du cloisonnement CSS pour les boutons

## v2.7.0 - 17/10/2026

**new features**

- Requêtes SQL par requête HTTP dans la devtool : chaque requête SQL exécutée par l'application est désormais **mesurée et rattachée au token de la requête HTTP** qui l'a déclenchée (comme le *Doctrine panel* de Symfony), afin d'identifier les requêtes lentes et les requêtes répétées directement depuis le profiler. En mode dev (`APP_ENV=dev` + extension `pdo_sqlite`), la nouvelle fabrique `Studoo\EduFramework\Core\Service\PdoFactory` retourne une connexion `TraceablePDO` (qui étend `PDO` : `DatabaseService::getConnect()` et le code applicatif restent inchangés) ; `TraceablePDO` mesure `query()` et `exec()`, `TraceablePDOStatement` mesure chaque `execute()` de statement préparé (via `PDO::ATTR_STATEMENT_CLASS`), avec la durée en ms, le nombre de lignes retournées/affectées et le token capturé au moment de l'exécution. Le nouveau collecteur `Studoo\EduFramework\Core\Logs\SqlQueryLogger` met ces requêtes en mémoire tampon puis les écrit **groupées en une transaction** en fin de requête (`finally` de `LoadCouchCore`, comme `HttpRequestLogger::finish()`) dans la nouvelle table `serv_sql_logs` de la base des logs (`var/logs/core_logs.sqlite`, colonnes `request_id` (token), `query`, `duration_ms`, `rows_count`, indexée sur `request_id`). **En production (`APP_ENV=prod`), la fabrique retourne une connexion `PDO` native : les classes de tracing ne sont ni instanciées ni chargées par l'autoloader, aucune mesure ni écriture n'a lieu** ; les requêtes hors requête HTTP journalisée (CLI, routes internes du profiler, base des logs elle-même) ne sont jamais collectées. Le profiler gagne une **vue SQL par requête** (`/_debug/profiler#sql/{token}`, entrée SQL de la navigation latérale) : tableau chronologique des requêtes (requête, durée colorée au-delà de 20/100 ms, lignes, heure), badge `×N requêtes identiques — X ms au total` sur les requêtes répétées, temps SQL cumulé et part du temps de la requête HTTP (Exemple : `12,3 ms — 34 % du temps de la requête HTTP (36,1 ms)`), accessible depuis la nouvelle ligne **Requêtes SQL** du descriptif de la requête (`N requêtes — X ms`) et depuis le nouveau **bloc SQL de la barre de debug** (nombre de requêtes et temps cumulé de la requête en cours, requête la plus lente au survol, lien vers la vue SQL ; masqué sans requête collectée). Le nouvel endpoint interne `GET /edu-sql-logs?token={token}` (enregistré uniquement en mode dev, non journalisé, token préfixé par `#` accepté) retourne la liste chronologique, les groupes de requêtes identiques (nombre d'exécutions, temps cumulé) et les totaux au format JSON ; `LogsService` expose les méthodes de lecture associées (`getSqlQueries()`, `countSqlQueries()`, `getSqlGroups()`)

- Barre de debug : le panneau du bloc **DEV** liste désormais les **services de développement** avec leurs URLs — application en HTTP (`http://localhost:80`) et HTTPS (`https://localhost:443`), MariaDB (`localhost:3306`), Mailpit (interface web `http://localhost:8025`, SMTP `localhost:1025`), DbGate (`http://localhost:8082`) et phpMyAdmin (`http://localhost:8081`). Les URLs des interfaces web sont cliquables et s'ouvrent dans un nouvel onglet. Les ports sont lus dans le `.env` (`HTTP_PORT`, `HTTPS_PORT`, `DB_HOST`/`DB_SOCKET`, `MAILER_UI_PORT`, `MAILER_SMTP_PORT`, `DBGATE_PORT`, `PMA_PORT`) avec les valeurs par défaut du `compose.yaml` si une variable est absente ; le conteneur `frankenphp` reçoit désormais ces variables pour que les URLs restent correctes sous Docker si les ports sont personnalisés
- Logs des erreurs : toute exception attrapée par le framework (`ErrorHttpStatusException` comme `Throwable`) est désormais journalisée avec la requête HTTP via la nouvelle méthode `HttpRequestLogger::setError()` — le log porte une clé `error_message` au format `Classe : message (fichier, ligne)`. Exemple pour une erreur 500 : `ParseError : syntax error, unexpected token "return" (fichier app/Controller/HomeController.php, ligne 17)`. L'objectif : donner aux développeurs la cause et la localisation exactes de l'erreur directement dans les logs, sans devoir reproduire le bug. Le message apparaît dans le contexte du log du profiler (recherche possible par message, classe, fichier ou ligne via le champ **Recherche**) et une ligne **Erreur** s'affiche en rouge sur la page descriptif de la requête (`/_debug/profiler#request/{token}`). Une requête sans erreur (y compris les 404 du dispatcher) ne porte pas la clé. La description est réinitialisée après chaque requête
- Exécution de l'application dans Docker via [FrankenPHP](https://frankenphp.dev){:target="_blank"} : le fichier `compose.yaml` inclut un service web `frankenphp` (image `dunglas/frankenphp:php8.4`) qui sert l'application sur le port 80. Le nouveau `Dockerfile` déclare les extensions requises par le framework (`pdo_sqlite`, `pdo_mysql`, `mbstring`, `openssl`, `zip`, `intl`, `opcache`) et le nouveau `Caddyfile` pilote le serveur (HTTP en développement, HTTPS automatique via la variable `SERVER_NAME`). Les variables du `.env` sont transmises au conteneur avec trois surcharges réseau (`DB_HOST=database`, `DB_SOCKET=3306`, `MAILER_DSN=smtp://mailer:1025`), les noms de conteneurs sont préfixés par `APP_NAME` et les ports publiés sont paramétrables (`HTTP_PORT`, `HTTPS_PORT`, `SERVER_NAME`, `DB_SOCKET`, `PMA_PORT`, `DBGATE_PORT`, `MAILER_SMTP_PORT`, `MAILER_UI_PORT`) pour faire cohabiter plusieurs projets. Le mode HTTPS local s'active par `SERVER_NAME=localhost` (certificat auto-signé de l'autorité locale Caddy, `https://127.0.0.1` non supporté — limitation connue de Docker). Un healthcheck MariaDB conditionne le démarrage du serveur web. Le flux classique `php bin/edu start` reste inchangé (services seuls : `docker compose up -d database mailer dbgate phpmyadmin`)

**bug Fixes**

- Profiler : les requêtes servies par FrankenPHP ne s'enregistraient pas dans la base des logs — seul le serveur PHP intégré (`php bin/edu start`) remplissait `var/logs/core_logs.sqlite`, en analysant sa sortie console, sans qu'aucun indicateur de performance par requête ne soit disponible. La nouvelle classe `Studoo\EduFramework\Core\Logs\HttpRequestLogger` journalise désormais **chaque requête traitée par le framework (`LoadCouchCore`), quel que soit le SAPI** (serveur intégré `cli-server`, FrankenPHP, FPM, ...), sans jamais faire échouer la requête et uniquement en mode dev (`APP_ENV=dev`). L'événement conserve le format du serveur intégré (`raw`, `timestamp`, `ip_port`, `status_code`, `method`, `path`) et porte trois nouvelles clés : `sapi` (serveur à l'origine de la requête, affiché et filtrable dans le profiler via le champ **SAPI** et le paramètre `sapi` de l'API `/edu-logs`), `execution_time_ms` et `memory_mb` (temps d'exécution et pic mémoire mesurés comme la barre de debug, affichés dans le contexte des logs et le bandeau de la requête). Comme le profiler de Symfony, la ligne de log est **réservée en début de requête** (`HttpRequestLogger::start()`) puis **complétée en fin de requête** (`finish()`) : le bloc DevTool de la barre de debug affiche le token de la requête en cours et pointe vers la nouvelle page **descriptif de la requête** du profiler (`/_debug/profiler#request/{token}` : statut, méthode, URL, IP, SAPI, durée, mémoire et ligne brute — les URL de la liste des requêtes sont cliquables et l'entrée « Requête » de la navigation latérale est activée). À cette occasion, `LogsService::addLog()` retourne l'identifiant du log et la nouvelle méthode `LogsService::updateLog()` permet de compléter un log existant. La commande `php bin/edu start` n'écrit plus dans la base : elle affiche la sortie du serveur en console (`BufferToServer` reste disponible pour analyser une ligne de log du serveur intégré). Seules les requêtes exécutées par PHP sont journalisées : les fichiers statiques servis directement par le serveur web ne le sont plus. La base est partagée entre les SAPI via le volume Docker avec `PRAGMA busy_timeout = 5000` contre les verrous. La barre de pagination du profiler (compteur de pages et boutons Précédent/Suivant) est supprimée : la navigation se fait par les filtres, le sélecteur « Résultats » (10/20/50/100) et les raccourcis « 10 derniers » / « Dernier » (le paramètre `page` de l'API `/edu-logs` reste supporté pour un usage programmatique)

**documentation**

- new [L'application dans Docker](docs/installation/docker.md) : architecture du stack (diagramme Mermaid), démarrage/arrêt, variables d'environnement, ports et coexistence de plusieurs projets, mode HTTPS local (certificat auto-signé, limitation `https://127.0.0.1`), vérification des prérequis dans le conteneur (`php bin/edu check:config`), dépannage
- update [Démarrer les services](docs/installation/start-services.md) : passage de MySQL à MariaDB, nouveau service FrankenPHP dans le tableau des ports, commandes par version, correction du lien "Installer les services directement sur votre machine" et du chemin de la connexion SQLite de dbgate
- update [La barre de debug et le profiler](docs/boost/debug-bar.md) : collecte de toutes les requêtes par `HttpRequestLogger` quel que soit le serveur (SAPI, temps d'exécution et mémoire par requête), token de la requête dans le bloc DevTool, nouvelle page descriptif d'une requête (`#request/{token}`), profiler disponible sur les deux serveurs (`http://localhost:8042/_debug/profiler` et `https://localhost/_debug/profiler`), limitations (fichiers statiques non journalisés, IP du réseau Docker, mode worker FrankenPHP) ; journalisation du message d'erreur avec la requête (clé `error_message`) et affichage sur la page descriptif ; liste des services de développement (URLs cliquables) dans le panneau du bloc DEV ; requêtes SQL par requête HTTP : bloc SQL de la barre de debug (nombre de requêtes, temps cumulé, requête la plus lente, lien vers la vue SQL), nouvelle vue SQL du profiler (`#sql/{token}` : tableau chronologique, badge des requêtes identiques, part du temps de la requête HTTP, accès depuis le descriptif de la requête), fonctionnement de la collecte (`PdoFactory`/`TraceablePDO`/`TraceablePDOStatement`/`SqlQueryLogger`, dev uniquement), nouvel endpoint `/edu-sql-logs` (paramètre `token`, format de réponse avec `groups`), table `serv_sql_logs` dans le stockage des logs et limitations (requêtes exécutées après le rendu non comptées dans le bloc de la barre, aucune collecte en production)
- update [La gestion des erreurs HTTP](docs/boost/http-error.md) : en mode dev, l'exception à l'origine de la page d'erreur est également journalisée avec la requête

## v2.6.3 - 15/10/2026

**bug Fixes**

- Mise à jour de `zircote/swagger-php` (4.x-dev → 5.8.*) : la version embarquée n'était pas compatible avec PHP 8.4 et supérieur — la commande `vendor/bin/openapi` affichait l'erreur `Constant E_STRICT is deprecated`, suivie de nombreux avertissements `SplObjectStorage` sous PHP 8.5, et imposait un script PHP de contournement pour générer la spécification. La génération (CLI et programmatique) fonctionne désormais sans erreur ni avertissement. Aucun impact sur le code applicatif : les attributs `OpenApi\Attributes` (`Get`, `Post`, `Response`, `Parameter`, `Info`) et la classe `OpenApi\Generator` restent identiques, le code généré par `make:api` est inchangé

## v2.6.2 - 15/10/2026

**new features**

- Documentation : support Mermaid (thème clair/sombre automatique) et 9 schémas fidèles au code du framework — le schéma CSRF devient un diagramme de séquence (remplace l'ASCII), nouveaux flowcharts : cycle d'une requête MVC, cycle de vie d'un controller, pipeline des erreurs HTTP, flux des logs vers le profiler, génération d'URL par nom de route ; nouveaux diagrammes de classes : CRUD (VilleController / VilleRepository / Ville / DatabaseService) et connexion DatabaseService (adaptateurs mysql, mariadb, sqlite) ; diagramme de séquence du pattern PRG (Post/Redirect/Get). Les schémas statiques controller.png/svg sont supprimés (source .excalidraw conservée)

## v2.6.1 - 15/10/2026

**new features**

- Documentation : migration de MkDocs vers [ProperDocs](https://github.com/ProperDocs/properdocs), le fork maintenu de MkDocs 1.x — MkDocs n'est plus maintenu depuis août 2024 et son dépôt est fermé aux contributions extérieures. ProperDocs est un remplacement direct (drop-in) : le thème Material et tous les plugins (literate-nav, git-committers, git-revision-date-localized, social, offline, search) restent inchangés. Les commandes `mkdocs build`/`mkdocs serve` deviennent `properdocs build`/`properdocs serve` (CI GitHub Pages, tâche `start-doc` du TaskFile, instructions contributeurs) et la dépendance `mkdocs` devient `properdocs` dans requirements.txt

## v2.6.0 - 14/10/2026

**new features**

- Nouveau service `CsrfService` pour protéger les formulaires contre les attaques CSRF (Cross-Site Request Forgery) selon le pattern "Synchronizer Token" : jeton aléatoire (CSPRNG) cloisonné par nom de route, comparaison protégée contre les attaques temporelles (`hash_equals`), rotation du jeton après chaque validation réussie. Nouvelle fonction Twig `csrf_token('nom_route')` pour générer le champ caché du formulaire et méthode `Request::isCsrfTokenValid('nom_route')` pour la validation dans le controller (le jeton peut aussi être transmis dans l'en-tête HTTP `X-CSRF-TOKEN` pour les appels AJAX). Démo applicative sur la route `/medecin/import` et documentation complète [docs/build/csrf.md](docs/build/csrf.md)

## v2.5.0 - 08/10/2026

**new features**

- [#149](https://github.com/studoo-app/edu-framework/issues/149) Nouvelle commande `php bin/edu make:entity` : génération de l'entité (`app/Entity`) et de son repository (`app/Repository`) — mode interactif ou option `--fields`, getters et setters fluides camelCase typés, hydratation avec casts dans le repository
- [#150](https://github.com/studoo-app/edu-framework/issues/150) Les paramètres d'identité du framework (name, version, date_version, php_version) sont personnalisables via le fichier `eduframe.yml` à la racine du framework (lu depuis `vendor/` dans les projets) — plus besoin de modifier `ConfigCore.php`, exception `ErrorConfigException` explicite si le fichier est invalide
- [#76](https://github.com/studoo-app/edu-framework/issues/76) Pages d'erreur HTTP mises en forme (404, 405, 403, 500) : templates autonomes et stylés dans `app/Template/error/`, nouveau `HttpError403Controller`, la classe `ErrorHttpStatusException` permet de lever une erreur HTTP depuis un controller (Exemple : accès interdit 403), toute exception non gérée affiche la page 500 avec le message de l'exception en mode développement (masqué en production). Le template `http-Default.html.twig` devient `http-500.html.twig`. Le nom du framework n'est plus affiché sur les pages d'erreur (titre et pied de page)

**bug Fixes**

- [#76](https://github.com/studoo-app/edu-framework/issues/76) Correction de l'affichage de la barre de debug : les styles globaux de la page hôte (ex: `a { margin-top: 1.5rem; }`) ne cassent plus l'alignement des blocs de la barre — reset CSS sur les liens de la toolbar, immunisée contre les feuilles de style de l'application

## v2.4.0 - 08/10/2026

**new features**

- Un controller peut maintenant gérer plusieurs routes via plusieurs méthodes : la clé `controller` du fichier "app/Config/routes.yaml" accepte la syntaxe `Controller\VilleController::index`. Sans méthode explicite, la méthode `execute()` est appelée comme avant (100 % rétro-compatible)
- La méthode appelée est validée par le framework (publique, non statique, paramètre de type Request, retour string|null) et lève une `ErrorControllerException` avec un message explicite en cas d'erreur
- [#75](https://github.com/studoo-app/edu-framework/issues/75) Gestion des fichiers téléversés (`$_FILES`) dans `Request` : normalisation automatique de la structure (champ simple et multi-fichiers), méthodes `hasFile()`, `getFile()`, `getFiles()`, `isValid()`, `getExtension()` et `move()`, démo applicative `/medecin/import` et documentation complète
- [#64](https://github.com/studoo-app/edu-framework/issues/64) Activation du cache TWIG : les templates compilés sont stockés dans `var/cache/twig` (nouvelle clé de configuration `cache_path`), recompilation automatique des templates modifiés (`auto_reload`)
- [#65](https://github.com/studoo-app/edu-framework/issues/65) Nouvelle commande `php bin/edu cache:clear` pour supprimer le cache de l'application (dossier `var/cache`)
- Documentation : nouvelle section "Un controller, plusieurs routes" dans [docs/build/controller-edu.md](docs/build/controller-edu.md)

**bug Fixes**

- Le fichier de configuration des routes est vérifié avant lecture : une exception `ErrorRouteConfigNotExistException` avec un message explicite (chemin attendu, configuration `route_config_path`, sensibilité à la casse sur Linux) remplace le fatal error du composant Yaml quand "routes.yaml" est introuvable

## v2.3.2 - 05/10/2026

**new features**

- Add documentation for building APIs and CRUD operations @bfoujols

**bug Fixes**

- [#143](https://github.com/studoo-app/edu-framework/issues/143) Mettre à jour les chemins de sqlite et des logs pour utiliser des chemins relatifs @bfoujols
- fix: mettre à jour la vérification de version PHP à 8.4 et ajuster les messages d'erreur @bfoujols
- test: remplacer sha1 par hash('sha256', ...) dans FastRouteCoreTest @bfoujols

> Release notes for v2.3.2
>
> [https://github.com/studoo-app/edu-framework/milestone/v2.3.2](https://github.com/studoo-app/edu-framework/milestone/21)

<br>

## v2.3.1 - 05/10/2026

**bug Fixes**

- [#143](https://github.com/studoo-app/edu-framework/issues/143) Mettre à jour les chemins de sqlite et des logs pour utiliser des chemins relatifs @bfoujols

> Release notes for v2.3.1
>
> [https://github.com/studoo-app/edu-framework/milestone/v2.3.1](https://github.com/studoo-app/edu-framework/milestone/20)

<br>

## v2.3.0 - 05/10/2026

**new features**

- [#136](https://github.com/studoo-app/edu-framework/issues/136) Implement SQLite logging service for server request logs @bfoujols
- [#136](https://github.com/studoo-app/edu-framework/issues/136) Add BufferFormat and BufferToServer classes for log formatting @bfoujols
- [#136](https://github.com/studoo-app/edu-framework/issues/136) Add logs navigation in the debug bar @bfoujols
- [#136](https://github.com/studoo-app/edu-framework/issues/136) Replace in-bar logs panel with a Symfony-style profiler page @bfoujols
- [#136](https://github.com/studoo-app/edu-framework/issues/136) Enhance filtering capabilities and add status family counting @bfoujols
- [#69](https://github.com/studoo-app/edu-framework/issues/69) Add custom listening socket (#69) Thanks @CapelleGab
- [#122](https://github.com/studoo-app/edu-framework/issues/122) Add Manage DB by dbgate @bfoujols
- [#120](https://github.com/studoo-app/edu-framework/issues/120) Add MailPit Service @bfoujols
- [#97](https://github.com/studoo-app/edu-framework/issues/97) Add slash command @bfoujols
- Update PHP minimum version 8.2 -> 8.4 (composer, bin/edu, CI, doc) @bfoujols
- Update dependencies (vlucas/phpdotenv v5.7, nette/php-generator, zircote/swagger-php, symfony/yaml 6.3 -> 6.4) @bfoujols

**bug Fixes**

- [#136](https://github.com/studoo-app/edu-framework/issues/136) Make SQLite logging non-blocking and resilient @bfoujols
- [#136](https://github.com/studoo-app/edu-framework/issues/136) Send proper HTTP status codes from error controllers @bfoujols
- [#136](https://github.com/studoo-app/edu-framework/issues/136) Gate debug routes on SQLite availability to prevent PDOException @bfoujols
- [#136](https://github.com/studoo-app/edu-framework/issues/136) Buffer stream chunks into complete lines to preserve adjacent request logs @bfoujols
- [#130](https://github.com/studoo-app/edu-framework/issues/130) github action E: Package 'netcat' has no installation candidate @bfoujols
- [#129](https://github.com/studoo-app/edu-framework/issues/129) run command taskfile before-commit @bfoujols
- Fix CI failure on profiler page and logs endpoint @bfoujols
- Fix CI failure on debug toolbar (missing semicolon) and deprecated dynamic property @bfoujols
- Fix debug toolbar always showing an empty controller instead of "aucun" (Request::hasHander always true) @bfoujols
- Fix PHP 8.5 deprecation (PDO::MYSQL_ATTR_INIT_COMMAND -> PDO\MYSQL::ATTR_INIT_COMMAND) @bfoujols
- Fix FastRouteCoreTest failures caused by APP_ENV state leaking between tests @bfoujols
- Fix security advisories on symfony/yaml (CVE-2026-45304, CVE-2026-45305, CVE-2026-45133) @bfoujols

**documentation**

- new [Migrer de la version 2.2 à la version 2.3](docs/migrate/migration-2_2-2_3.md)
- new [La barre de debug et le profiler](docs/boost/debug-bar.md)
- update [Start/Stop l'application](docs/installation/start-application.md) : options de la commande `start`
- update [Comment installer les services](docs/installation/start-services.md) : MailPit et dbgate
- update [Avant de démarrer](docs/installation/prerequis.md) : extension `pdo_sqlite` et PHP 8.4 minimum
- update [L'arborescence du projet](docs/build/arborescence.md) : correction du fichier `compose.yaml`

> Release notes for v2.3.0
>
> [https://github.com/studoo-app/edu-framework/milestone/v2.3.0](https://github.com/studoo-app/edu-framework/milestone/19)

<br>


## v2.2.1 - 23/08/2024

**new features**

- [#119](https://github.com/studoo-app/edu-framework/issues/119) add Request::getBody() @bfoujols

**bug Fixes**

- [#117](https://github.com/studoo-app/edu-framework/issues/117) Fix class not use in API controller @bfoujols
- Fix Doc install pip package

> Release notes for v2.2.1
>
> [https://github.com/studoo-app/edu-framework/milestone/v2.2.1](https://github.com/studoo-app/edu-framework/milestone/18)

<br>

## v2.2.0 - 08/08/2024

**new features**

- [#112](https://github.com/studoo-app/edu-framework/issues/112) Implement OpenAPI @bfoujols

**bug Fixes**

- [#114](https://github.com/studoo-app/edu-framework/issues/114) Update PHP version 8.1 -> 8.2 @bfoujols
- [#116](https://github.com/studoo-app/edu-framework/pull/116) Add Tests for OpenAPI, Command Cli @bfoujols

> Release notes for v2.2.0
>
> [https://github.com/studoo-app/edu-framework/milestone/v2.2.0](https://github.com/studoo-app/edu-framework/milestone/17)

<br>

## v2.1.0 - 20/06/2024

**new features**

- [#87](https://github.com/studoo-app/edu-framework/issues/87) À partir du nom d'une route, reconstruire URL getNameToPath()
- [#39](https://github.com/studoo-app/edu-framework/issues/39) Mise en place SQLite
- [#105](https://github.com/studoo-app/edu-framework/issues/105) Nouvelle documentation [https://studoo-app.github.io/edu-framework](https://studoo-app.github.io/edu-framework)
- [#86](https://github.com/studoo-app/edu-framework/issues/86) Implement codeSpace by GitHub

**bug Fixes**

- [#96](https://github.com/studoo-app/edu-framework/issues/96) Problème d'installation de phpunit 11 @pbentura @RaphaelBensoussan
- [#92](https://github.com/studoo-app/edu-framework/issues/92) Problème de TestUnit sur FastRouteCore

> Release notes for v2.1.0
>
> [https://github.com/studoo-app/edu-framework/milestone/v2.1.0](https://github.com/studoo-app/edu-framework/milestone/12)

<br>

## v2.0.2 - 24/05/2024

**bug Fixes**

- [#94](https://github.com/studoo-app/edu-framework/issues/94) fix patch root by @bfoujols in <https://github.com/studoo-app/edu-framework/pull/95>

> Release notes for v2.0.2
>
> [https://github.com/studoo-app/edu-framework/milestone/v2.0.2](https://github.com/studoo-app/edu-framework/milestone/15)

<br>


## v2.0.1 - 15/05/2024

**bug Fixes**

- [#88](https://github.com/studoo-app/edu-framework/issues/88) Correction sur le probleme avec DatabaseService dans la ligne de command

> Release notes for v2.0.1
>
> [https://github.com/studoo-app/edu-framework/milestone/v2.0.1](https://github.com/studoo-app/edu-framework/milestone/14)

  <br>

## v2.0.0 - 06/04/2024

**new features**

- [#73](https://github.com/studoo-app/edu-framework/issues/73) ajout de la commande `php bin/edu make:api` pour générer un controller de type API
- [#70](https://github.com/studoo-app/edu-framework/issues/70) ajout de la commande `php bin/edu make:command` pour générer une commande console
- [#59](https://github.com/studoo-app/edu-framework/issues/59) ajout d'une page par défaut sur la route

**deprecations**

- [#68](https://github.com/studoo-app/edu-framework/issues/68) Suppression des anciennes configurations Docker
- [#72](https://github.com/studoo-app/edu-framework/issues/72) Bootstrap 5.3 Clean

**bug Fixes**

- [#83](https://github.com/studoo-app/edu-framework/issues/83) Docker compose.yaml: version is obsolete
- [#78](https://github.com/studoo-app/edu-framework/issues/78) Barre de debug dans les pages d'erreur

**documentation**

- Nouvelle interface et organisation par chapitre
- new [Request : La gestion des requêtes HTTP](https://studoo-app.github.io/edu-framework/boost/resquet.html)
- update [Comment installer EduFrame](https://studoo-app.github.io/edu-framework/build/index.html) : Nouvelle installation par version
- update [Comment installer les services](https://studoo-app.github.io/edu-framework/installation/index.html) : Refactoring Docker
- update [Arborescence](https://studoo-app.github.io/edu-framework/installation/index.html) : Nouvelle arbo v2.0
- update [Comment faire un controller](https://studoo-app.github.io/edu-framework/build/index.html) : Plus de detail
- update [Comment faire un post dans un controller](https://studoo-app.github.io/edu-framework/build/index.html) : Plus de detail

> Release notes for v2.0.0
>
> [https://github.com/studoo-app/edu-framework/milestone/v2.0.0](https://github.com/studoo-app/edu-framework/milestone/11?closed=1)

  <br>

## v1.2.0 - 25/03/2024

**new features**

- [#66](https://github.com/studoo-app/edu-framework/issues/66) ajout de la commande `php bin/edu start` pour démarrer le serveur de développement
- [#62](https://github.com/studoo-app/edu-framework/issues/62) ajout de la commande `php bin/edu check:config` pour vérifier la configuration du framework
- [#26](https://github.com/studoo-app/edu-framework/issues/26) Implement PHPDebugBar

**bug fixes**

- [#81](https://github.com/studoo-app/edu-framework/issues/81) Probleme de récupération des variables dynamiques passées dans la route via {}

> Release notes for v1.2.0
>
> [https://github.com/studoo-app/edu-framework/milestone/v1.2.0](https://github.com/studoo-app/edu-framework/milestone/11?closed=1)

  <br>

## v1.1.0 - 03/2024

Version beta de la bar de debug avec les fonctionnalités suivantes :

- Mise en place de la bar de debug en beta sur environnement de développement
- Refonte de docker pour l'installation des services
- Correction de bugs mineurs
  - Docker : correction sur le reseau des services Mydql et PhpMyAdmin

## v1.0.0 - 2024

Version définitive du framework avec les fonctionnalités suivantes :

- Création de controller via la commande `php bin/edu make:controller`

## v0.6.0 - 2023

Version stable du framework avec les fonctionnalités suivantes :

- Création de controller
- Création de template
- Création de route
- Mise en place de la base de données

## v0.5.0 - 2023

Versions de développement du framework avec les fonctionnalités suivantes :

- Mise en place et conception de l'architecture du framework (MVC)
- plusieurs POC des couches du MVC
- Tests unitaires
- Mise en place de la documentation du framework (Writerside)
- Gestion de la configuration du framework (DotEnv)
- Gestion de dépendances (Composer)
- Gestion des services (Docker)

## v0.1.0 - 2022

Début du projet Edu Framework