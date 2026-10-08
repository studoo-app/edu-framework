# CHANGELOG

**Présentation des versions du framework Edu Framework**

## v2.5.0 - 08/10/2026

**new features**

- [#149](https://github.com/studoo-app/edu-framework/issues/149) Nouvelle commande `php bin/edu make:entity` : génération de l'entité (`app/Entity`) et de son repository (`app/Repository`) — mode interactif ou option `--fields`, getters et setters fluides camelCase typés, hydratation avec casts dans le repository
- [#150](https://github.com/studoo-app/edu-framework/issues/150) Les paramètres d'identité du framework (name, version, date_version, php_version) sont personnalisables via le fichier `eduframe.yml` à la racine du framework (lu depuis `vendor/` dans les projets) — plus besoin de modifier `ConfigCore.php`, exception `ErrorConfigException` explicite si le fichier est invalide
- [#76](https://github.com/studoo-app/edu-framework/issues/76) Pages d'erreur HTTP mises en forme (404, 405, 403, 500) : templates autonomes et stylés dans `app/Template/error/`, nouveau `HttpError403Controller`, la classe `ErrorHttpStatusException` permet de lever une erreur HTTP depuis un controller (Exemple : accès interdit 403), toute exception non gérée affiche la page 500 avec le message de l'exception en mode développement (masqué en production). Le template `http-Default.html.twig` devient `http-500.html.twig`. Le nom du framework n'est plus affiché sur les pages d'erreur (titre et pied de page)

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