# CHANGELOG

**Présentation des versions du framework Edu Framework**

## v2.7.3 - 17/10/2026

**new features**

- Profiler : la vue Recherche (`/_debug/profiler#search`) gagne les **mêmes onglets de filtrage par famille de statut que la vue des logs** — `Tous / 2xx / 3xx / 4xx / 5xx`, avec le nombre de requêtes par famille en badge. Un clic filtre les résultats sur la famille sélectionnée (l'onglet `Tous` affiche toutes les requêtes), l'onglet actif est mis en évidence et le champ **Statut** du formulaire de recherche est synchronisé ; les compteurs proviennent de l'API `/edu-logs` (calculés côté serveur, ils couvrent l'ensemble des requêtes correspondant aux autres filtres) et restent indépendants du filtre de statut, comme dans la vue des logs

**documentation**

- update [La barre de debug et le profiler](boost/debug-bar.md) : onglets de filtrage par famille de statut documentés pour les vues **Logs** et **Recherche** (clic pour filtrer, onglet actif, synchronisation du champ Statut du formulaire)

## v2.7.2 - 17/10/2026

**bug Fixes**

- Barre de debug : correction finale de l'alignement du bouton de fermeture à droite (styles flex, hauteur et alignement)

## v2.7.1 - 17/10/2026

**bug Fixes**

- Barre de debug : correction du décalage du bouton de fermeture à droite, amélioration du cloisonnement CSS pour les boutons

## v2.7.0 - 17/10/2026

**new features**

- Barre de debug : le bloc **DEV** liste les services de développement avec leurs URLs cliquables (application HTTP/HTTPS, MariaDB, Mailpit et son SMTP, DbGate, phpMyAdmin) — ports lus dans le `.env` (défauts du `compose.yaml`), variables transmises au conteneur `frankenphp`
- Exécution de l'application dans Docker via [FrankenPHP](https://frankenphp.dev){:target="_blank"} : nouveau service web `frankenphp` dans le `compose.yaml` (PHP 8.4, HTTPS et HTTP/3), `Dockerfile` avec les extensions requises par le framework, `Caddyfile` pilotable par `SERVER_NAME`, variables du `.env` transmises au conteneur (surcharges réseau `DB_HOST`, `DB_SOCKET`, `MAILER_DSN`), conteneurs préfixés par `APP_NAME`, ports et `SERVER_NAME` paramétrables dans le `.env` (mode HTTPS local : `SERVER_NAME=localhost`), healthcheck MariaDB — le flux classique `php bin/edu start` reste inchangé

**bug Fixes**

- Profiler : les requêtes servies par FrankenPHP ne s'enregistraient pas dans la base des logs — seul le serveur PHP intégré (`php bin/edu start`) la remplissait, sans indicateur de performance par requête. La nouvelle classe `HttpRequestLogger` journalise désormais **chaque requête traitée par le framework, quel que soit le SAPI** (serveur intégré, FrankenPHP, FPM, ...), avec le serveur à l'origine (`sapi`, affiché et filtrable dans le profiler via le champ **SAPI** et le paramètre `sapi` de l'API `/edu-logs`), le temps d'exécution (`execution_time_ms`) et la mémoire utilisée (`memory_mb`) par requête, mesurés comme la barre de debug. Comme le profiler de Symfony, la ligne est réservée en début de requête puis complétée en fin : le bloc DevTool de la barre de debug affiche le token de la requête et pointe vers la nouvelle page **descriptif de la requête** (`#request/{token}` : statut, méthode, URL, IP, SAPI, durée, mémoire, ligne brute). La commande `php bin/edu start` n'écrit plus dans la base (affichage console uniquement) : seules les requêtes exécutées par PHP sont journalisées (fichiers statiques servis directement par le serveur web exclus), et la base partagée entre les serveurs est protégée contre les verrous (`PRAGMA busy_timeout`). La barre de pagination du profiler est supprimée : la navigation se fait par les filtres, le sélecteur « Résultats » et les raccourcis « 10 derniers » / « Dernier » (le paramètre `page` de l'API reste supporté)

**documentation**

- new [L'application dans Docker](installation/docker.md) : architecture, démarrage/arrêt, variables d'environnement, ports et coexistence de plusieurs projets, mode HTTPS local (certificat auto-signé, limitation `https://127.0.0.1`), vérification des prérequis dans le conteneur, dépannage
- update [Démarrer les services](installation/start-services.md) : MySQL devient MariaDB, service FrankenPHP dans le tableau des ports, commandes par version, corrections de liens
- update [La barre de debug et le profiler](boost/debug-bar.md) : collecte de toutes les requêtes par `HttpRequestLogger` quel que soit le serveur (clé `sapi`, temps d'exécution et mémoire par requête), token de la requête dans le bloc DevTool, nouvelle page descriptif d'une requête (`#request/{token}`), profiler disponible sur les deux serveurs (`http://localhost:8042/_debug/profiler` et `https://localhost/_debug/profiler`), limitations (fichiers statiques non journalisés, IP du réseau Docker, mode worker FrankenPHP) ; liste des services de développement (URLs cliquables) dans le bloc DEV

## v2.6.3 - 15/10/2026

**bug Fixes**

- Mise à jour de `zircote/swagger-php` (4.x-dev → 5.8.*) : la librairie n'était pas compatible avec PHP 8.4 et supérieur — la commande `vendor/bin/openapi` affichait l'erreur `Constant E_STRICT is deprecated` et produisait un fichier incomplet

**documentation**

- update [Construire une API](build/controller-edu-api.md) : suppression de la note de contournement (script `generate-openapi.php`), la commande `vendor/bin/openapi` est de nouveau fonctionnelle avec PHP 8.4 et supérieur

## v2.6.2 - 15/10/2026

**documentation**

- new Support [Mermaid](https://mermaid.js.org){:target="_blank"} dans la documentation : les schémas s'adaptent automatiquement au thème clair/sombre et se chargent uniquement sur les pages qui en contiennent
- update [Protéger un formulaire (CSRF)](build/csrf.md) : le schéma ASCII devient un diagramme de séquence Mermaid
- new [Le MVC, c'est quoi ?](build/concept-mvc.md) : flowchart du cycle d'une requête avec les classes réelles (FastRouteCore, Controller, Repository, TwigCore)
- update [Comment faire un controller ?](build/controller-edu.md) : le schéma statique est remplacé par un flowchart du cycle de vie de HelloController
- new [Construire le CRUD](build/dataservice-select.md) : diagramme de classes VilleController / VilleRepository / Ville / DatabaseService
- new [Les erreurs HTTP](boost/http-error.md) : flowchart du pipeline d'erreurs avec les branches dev/production
- update [Comment faire un formulaire ?](build/controller-edu-post.md) : diagramme de séquence du pattern PRG (Post/Redirect/Get)
- new [DatabaseService : La gestion des données](boost/dataservice.md) : diagramme de classes de la connexion (adaptateurs mysql, mariadb, sqlite)
- new [La barre de debug et le profiler](boost/debug-bar.md) : flowchart du flux des logs vers le profiler
- new [Route : générer une URL à partir du nom d'une route](boost/route.md) : flowchart de la génération d'URL et de la BadRouteException

## v2.6.1 - 15/10/2026

**new features**

- Migration de MkDocs vers [ProperDocs](https://github.com/ProperDocs/properdocs){:target="_blank"}, le fork maintenu de MkDocs 1.x — MkDocs n'est plus maintenu depuis août 2024 et son dépôt est fermé aux contributions extérieures. ProperDocs est un remplacement direct (drop-in) : le thème Material et tous les plugins restent inchangés, la configuration devient `properdocs.yml` et les commandes `mkdocs build`/`mkdocs serve` deviennent `properdocs build`/`properdocs serve`

**bug Fixes**

- Les cartes sociales (og:image) sont de nouveau générées : le logo du plugin social est résolu via le logo du thème (préfixé par le dossier de documentation)

**documentation**

- new Use case "VILLE" : STEP 1 et Refactoring : ajout à la navigation (contenus retirés depuis)
- update [Contribuer pour la docs](contributor/documentation.md) : installation et lancement avec ProperDocs
- suppression de la page obsolète `dataservice-insert.md` (contenu intégré à [Construire le CRUD](build/dataservice-select.md))

## v2.6.0 - 14/10/2026

**new features**

- Nouveau service `CsrfService` pour protéger les formulaires contre les attaques CSRF (Cross-Site Request Forgery) selon le pattern "Synchronizer Token" : jeton aléatoire (CSPRNG) cloisonné par nom de route, comparaison protégée contre les attaques temporelles (`hash_equals`), rotation du jeton après chaque validation réussie. Nouvelle fonction Twig `csrf_token('nom_route')` pour générer le champ caché du formulaire et méthode `Request::isCsrfTokenValid('nom_route')` pour la validation dans le controller (le jeton peut aussi être transmis dans l'en-tête HTTP `X-CSRF-TOKEN` pour les appels AJAX). Démo applicative sur la route `/medecin/import` et documentation complète [docs/build/csrf.md](build/csrf.md)

## v2.5.0 - 08/10/2026

**new features**

- [#149](https://github.com/studoo-app/edu-framework/issues/149) Nouvelle commande `php bin/edu make:entity` : génération de l'entité (`app/Entity`) et de son repository (`app/Repository`) — mode interactif ou option `--fields`, getters et setters fluides camelCase typés, hydratation avec casts dans le repository @bfoujols
- [#150](https://github.com/studoo-app/edu-framework/issues/150) Les paramètres d'identité du framework (name, version, date_version, php_version) sont personnalisables via le fichier `eduframe.yml` à la racine du framework (lu depuis `vendor/` dans les projets) — plus besoin de modifier `ConfigCore.php`, exception `ErrorConfigException` explicite si le fichier est invalide @bfoujols
- [#76](https://github.com/studoo-app/edu-framework/issues/76) Pages d'erreur HTTP mises en forme (404, 405, 403, 500) : templates autonomes et stylés dans `app/Template/error/`, nouveau `HttpError403Controller`, la classe `ErrorHttpStatusException` permet de lever une erreur HTTP depuis un controller (Exemple : accès interdit 403), toute exception non gérée affiche la page 500 avec le message de l'exception en mode développement (masqué en production). Le template `http-Default.html.twig` devient `http-500.html.twig`. Le nom du framework n'est plus affiché sur les pages d'erreur (titre et pied de page) @bfoujols

**bug Fixes**

- [#76](https://github.com/studoo-app/edu-framework/issues/76) Correction de l'affichage de la barre de debug : les styles globaux de la page hôte (ex: `a { margin-top: 1.5rem; }`) ne cassent plus l'alignement des blocs de la barre — reset CSS sur les liens de la toolbar, immunisée contre les feuilles de style de l'application @bfoujols

**documentation**

- new [La commande make:entity](installation/command-edu.md) : génération d'une entité et de son repository
- update [Construire le CRUD](build/dataservice-select.md) : note sur la génération des fichiers avec `make:entity`
- update [Installation](installation/installation.md) : les paramètres du framework (eduframe.yml)
- new [Les erreurs HTTP](boost/http-error.md) : pages 403/404/405/500, personnalisation, `ErrorHttpStatusException`

> Release notes for v2.5.0
>
> [https://github.com/studoo-app/edu-framework/milestone/22](https://github.com/studoo-app/edu-framework/milestone/22)

  <br>

## v2.4.0 - 08/10/2026

**new features**

- [#147](https://github.com/studoo-app/edu-framework/issues/147) Un controller peut maintenant gérer plusieurs routes via plusieurs méthodes : la clé `controller` du fichier "app/Config/routes.yaml" accepte la syntaxe `Controller\VilleController::index`. Sans méthode explicite, la méthode `execute()` est appelée comme avant (100 % rétro-compatible) @bfoujols
- La méthode appelée est validée par le framework (publique, non statique, paramètre de type Request, retour string|null) et lève une `ErrorControllerException` avec un message explicite en cas d'erreur @bfoujols
- [#75](https://github.com/studoo-app/edu-framework/issues/75) Gestion des fichiers téléversés (`$_FILES`) dans `Request` : normalisation automatique de la structure (champ simple et multi-fichiers), méthodes `hasFile()`, `getFile()`, `getFiles()`, `isValid()`, `getExtension()` et `move()`, démo applicative `/medecin/import` @bfoujols
- [#64](https://github.com/studoo-app/edu-framework/issues/64) Activation du cache TWIG : les templates compilés sont stockés dans `var/cache/twig` (nouvelle clé de configuration `cache_path`), recompilation automatique des templates modifiés (`auto_reload`) @bfoujols
- [#65](https://github.com/studoo-app/edu-framework/issues/65) Nouvelle commande `php bin/edu cache:clear` pour supprimer le cache de l'application (dossier `var/cache`) @bfoujols

**bug Fixes**

- [#86](https://github.com/studoo-app/edu-framework/issues/86) Le fichier de configuration des routes est vérifié avant lecture : une exception `ErrorRouteConfigNotExistException` avec un message explicite (chemin attendu, configuration `route_config_path`, sensibilité à la casse sur Linux) remplace le fatal error du composant Yaml quand "routes.yaml" est introuvable @bfoujols

**documentation**

- new [Un controller, plusieurs routes](build/controller-edu.md) : la syntaxe `Controller\Class::method` dans le fichier des routes
- new [Gestion des fichiers (upload)](boost/resquet.md) : téléversement de fichiers dans Request
- update [La commande bin/edu](installation/command-edu.md) : commande `cache:clear`
- update [Installation](installation/installation.md) : erreur sur le fichier des routes et sensibilité à la casse sur Linux
- update [Route](boost/route.md) : note sur la syntaxe `Controller\Class::method`

> Release notes for v2.4.0
>
> [https://github.com/studoo-app/edu-framework/milestone/21](https://github.com/studoo-app/edu-framework/milestone/21)

  <br>

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

- new [Migrer de la version 2.2 à la version 2.3](migrate/migration-2_2-2_3.md)
- new [La barre de debug et le profiler](boost/debug-bar.md)
- update [Start/Stop l'application](installation/start-application.md) : options de la commande `start`
- update [Comment installer les services](installation/start-services.md) : MailPit et dbgate
- update [Avant de démarrer](installation/prerequis.md) : extension `pdo_sqlite` et PHP 8.4 minimum
- update [L'arborescence du projet](build/arborescence.md) : correction du fichier `compose.yaml`

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
> [https://github.com/studoo-app/edu-framework/milestone/18](https://github.com/studoo-app/edu-framework/milestone/18)

  <br>

## v2.2.0 - 08/08/2024

**new features**

- [#112](https://github.com/studoo-app/edu-framework/issues/112) Implement OpenAPI @bfoujols

**bug Fixes**

- [#114](https://github.com/studoo-app/edu-framework/issues/114) Update PHP version 8.1 -> 8.2 @bfoujols
- [#116](https://github.com/studoo-app/edu-framework/pull/116) Add Tests for OpenAPI, Command Cli @bfoujols

> Release notes for v2.2.0
>
> [https://github.com/studoo-app/edu-framework/milestone/17](https://github.com/studoo-app/edu-framework/milestone/17)

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