![separe](https://github.com/studoo-app/.github/blob/main/profile/studoo-banner-logo.png)
# Edu Framework
[![Codacy Badge](https://app.codacy.com/project/badge/Grade/a15f20cbdf2743618efe54e2db39f605)](https://app.codacy.com/gh/studoo-app/edu-framework/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_grade)
[![Codacy Badge](https://app.codacy.com/project/badge/Coverage/a15f20cbdf2743618efe54e2db39f605)](https://app.codacy.com/gh/studoo-app/edu-framework/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_coverage)
[![Latest Stable Version](https://poser.pugx.org/studoo/ecole-directe-api/v)](//packagist.org/packages/studoo/edu-framework)
[![Total Downloads](https://poser.pugx.org/studoo/ecole-directe-api/downloads)](//packagist.org/packages/studoo/edu-framework)
[![Latest Unstable Version](https://poser.pugx.org/studoo/ecole-directe-api/v/unstable)](//packagist.org/packages/edu-framework)
[![License](https://poser.pugx.org/studoo/ecole-directe-api/license)](//packagist.org/packages/studoo/edu-framework)

Voici une proposition d'architecture MVC pour l'élaboration d'un projet ou de TP en cours \
L'objectif pédagogique est :
- Appréhender un projet par couche via MVC
- Faire un projet "full POO" et dans les "best practices" attendu par les entreprises
- Orchestrer via un gestionnaire de package (composer)
- Développement de test unitaire
- Début d'approche pour l'enseignement d'une framework (symfony, slim, Laravel ...)

Cible :
- Première année / 2e semestre
- Deuxième année / 1re semestre

## Prérequis 
### Engine PHP
Pour l'utilisation de ce framework, il est nécessaire d'avoir une version de PHP supérieur à 8.0.0
### Composer
Composer est un gestionnaire de dépendance en PHP. Il permet de déclarer les bibliothèques dont dépend votre projet et il les installe pour vous.
Pour l'installation de composer, vous pouvez suivre le tutoriel suivant : [Composer](https://getcomposer.org/download/)

## Installation
Pour installer le projet, il faut cloner le projet sur votre machine
````shell
git clone git@github.com:studoo-app/edu-framework.git
````

Installer les dépendances du projet via composer
````shell
composer install
````

Initialiser le fichier de configuration
````shell
composer edu:init
````

## Démarrage du projet
Pour démarrer le projet, il faut lancer la commande suivante
````shell
composer edu:start
````

## Stack Tech
| Version | Service                                                             | DESCRIPTION                      |
|:--------|:--------------------------------------------------------------------|:---------------------------------|
| ^5.5    | [vlucas/phpdotenv](https://packagist.org/packages/vlucas/phpdotenv) | Loads environment variables      |
| ^3.5    | [twig/twig](https://packagist.org/packages/twig/twig)               | Template Engine (VIEW couch)     |
| ^1.3    | [nikic/fast-route](https://packagist.org/packages/nikic/fast-route) | Router Engine (CONTROLLER couch) |
| ^8.0    | [PHP Engine](https://www.php.net/downloads.php)                     | Engine PHP                       |  
| ^2.0    | [Composer](https://getcomposer.org/download/)                       | Dependency Manager               | 
| ^9.5    | [PHPUnit](https://phpunit.de/)                                      | Testing Engine                   |
