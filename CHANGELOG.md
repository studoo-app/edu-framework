# CHANGELOG

**Présentation des versions du framework Edu Framework**

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