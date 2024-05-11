![separe](https://raw.githubusercontent.com/studoo-app/.github/main/profile/studoo-banner-logo.png)
# Edu Framework
[![Codacy Badge](https://app.codacy.com/project/badge/Grade/a15f20cbdf2743618efe54e2db39f605)](https://app.codacy.com/gh/studoo-app/edu-framework/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_grade)
[![Codacy Badge](https://app.codacy.com/project/badge/Coverage/a15f20cbdf2743618efe54e2db39f605)](https://app.codacy.com/gh/studoo-app/edu-framework/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_coverage)
[![CodeFactor](https://www.codefactor.io/repository/github/studoo-app/edu-framework/badge)](https://www.codefactor.io/repository/github/studoo-app/edu-framework)
[![Latest Stable Version](https://poser.pugx.org/studoo/edu-framework/v)](//packagist.org/packages/studoo/edu-framework)
[![Total Downloads](https://poser.pugx.org/studoo/edu-framework/downloads)](//packagist.org/packages/studoo/edu-framework)
[![Latest Unstable Version](https://poser.pugx.org/studoo/edu-framework/v/unstable)](//packagist.org/packages/edu-framework)
[![License](https://poser.pugx.org/studoo/edu-framework/license)](//packagist.org/packages/studoo/edu-framework)

Le projet "Edu Framework", initié en août 2023 par une équipe de développeurs dévoués à l'enseignement et à la diffusion du savoir, vise à répondre à une problématique récurrente identifiée lors de nos formations : "Comment faire une transition vers Symfony ?".

L'objectif principal "Edu Framework" est de fournir un ensemble d'outils, de guides et de ressources pédagogiques permettant aux développeurs, qu'ils soient novices ou expérimentés, de faciliter leur transition vers le framework Symfony. Ce projet visera à couvrir les aspects fondamentaux ainsi que les techniques avancées de Symfony, en mettant l'accent sur les meilleures pratiques de développement.

## Objectifs pédagogiques

### 1. Appréhender un projet par couche via le concept d'architecture MVC (Model View Controller)

L'architecture MVC est un modèle de conception qui sépare une application en trois composants principaux : le modèle, la vue et le contrôleur. Cette séparation aide à organiser le code, le rend plus modulaire, et facilite la maintenance et l'évolutivité des applications.

- **Modèle (Model)** : Représente la structure des données. Le modèle gère les règles d'affaires, les requêtes de base de données, et les opérations de données.
- **Vue (View)** : Présente les données à l'utilisateur. La vue est la représentation visuelle des données, généralement les interfaces utilisateur.
- **Contrôleur (Controller)** : Agit comme un intermédiaire entre le modèle et la vue. Le contrôleur traite les entrées de l'utilisateur, manipule les données à travers le modèle et renvoie la sortie à la vue.

### 2. Faire un projet "full POO" et dans les "best practices" attendu par les entreprises

La programmation orientée objet (POO) est une méthode de programmation qui utilise des classes et des objets. Elle permet de structurer le code de manière à ce qu'il soit plus facile à comprendre, à maintenir et à réutiliser.

Les "best practices" incluent :

- **Encapsulation** : Garder les détails d'implémentation d'une classe à l'intérieur de celle-ci, et exposer uniquement les opérations nécessaires via des méthodes publiques.
- **Héritage** : Permet de créer de nouvelles classes basées sur des classes existantes.
- **Polymorphisme** : Permet de traiter les objets de différentes classes à travers une interface commune.
- **Principe SOLID** : Un ensemble de cinq principes de conception pour rendre le code plus compréhensible, flexible et maintenable.

### 3. Orchestrer via un gestionnaire de dépendance (Composer)

Composer est un outil de gestion de dépendances pour PHP. Il permet de déclarer les bibliothèques dont dépend votre projet et de les gérer (installer/mettre à jour).

- Utilisez le fichier `composer.json` pour déclarer les dépendances.
- Exécutez `composer install` pour installer les dépendances spécifiées.
- Utilisez l'autoloading PSR-4 pour charger automatiquement vos classes sans nécessiter de `require` ou `include` manuels.
- Analyser régulièrement les dépendances pour s'assurer qu'elles sont à jour et sécurisées.
- Comment choisir des dépendances de qualité et maintenues.

### 4. Développement de test unitaire (PHPUnit)

PHPUnit est un framework de test unitaire pour PHP. Les tests unitaires permettent de tester des parties isolées du code pour s'assurer qu'elles fonctionnent comme prévu.

- Écrivez des tests qui couvrent à la fois les cas d'utilisation normaux et les cas limites.
- Exécutez régulièrement les tests pour garantir que les modifications n'introduisent pas de régressions.
- Intégrez les tests unitaires dans un pipeline CI/CD pour automatiser les tests.

### 5. Début d'approche pour l'enseignement d'un framework (Symfony, Laravel ...)

Introduire un framework comme Symfony ou Laravel permet de montrer comment les concepts de MVC, POO, et les best practices sont appliqués dans un environnement de développement professionnel.

- Commencez par expliquer l'architecture du framework et son cycle de requête/réponse, systeme de routing, etc.
- Enseignez comment créer des modèles, des vues, et des contrôleurs dans le contexte du framework.
- Montrez comment utiliser les outils intégrés pour la sécurité, les formulaires, la validation, etc.
- Expliquez comment configurer et personnaliser le framework pour répondre aux besoins du projet.

### 6. Option DevOps (Docker, Deployer ...)

L'introduction au DevOps et aux outils comme Docker et Deployer aide les étudiants à comprendre l'importance de l'automatisation et de la gestion de l'infrastructure comme code.

- **Docker** : Enseignez la création de conteneurs pour les environnements de développement et de production, facilitant ainsi le déploiement et la scalabilité.
- **Deployer** : Montrez comment automatiser le déploiement des applications sur différents serveurs.

Ces objectifs pédagogiques fournissent une base solide pour les étudiants en code applicatif et architecture applicative, les préparant pour les défis du développement moderne et les attentes du monde professionnel.

> Ce framework n'est pas adapté à une utilisation en production. Il est destiné à des fins pédagogiques.

## Documentation
L'ensemble de la documentation est disponible sur le [https://studoo-app.github.io/edu-framework-doc](https://studoo-app.github.io/edu-framework-doc/)

## Equipe de développement
L'équipe de développement du projet "Edu Framework" est composée de développeurs expérimentés et passionnés par l'enseignement et la transmission du savoir. 
Chaque membre de l'équipe apporte son expertise et son expérience pour créer un outil pédagogique de qualité.
Un collectif, appelé [Studoo](https://github.com/studoo-app), est né autour de ces projets pour partager des connaissances et des compétences, et pour contribuer à l'amélioration continue des outils pédagogiques.

- **Founder / Lead tech** : [Benoit Foujols](https://github.com/bfoujols)
- **Lead dev** : [Julien Pechberty](https://github.com/JPechberty)


