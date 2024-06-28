# Objectifs pédagogiques

## Framework

**Début d'approche pour l'enseignement d'un framework (Symfony, Laravel ...)**

Introduire un framework comme Symfony ou Laravel permet de montrer comment les concepts de MVC, POO, et les best practices sont appliqués dans un environnement de développement professionnel.

- Commencez par expliquer l'architecture du framework et son cycle de requête/réponse, systeme de routing, etc.
- Enseignez comment créer des modèles, des vues, et des contrôleurs dans le contexte du framework.
- Montrez comment utiliser les outils intégrés pour la sécurité, les formulaires, la validation, etc.
- Expliquez comment configurer et personnaliser le framework pour répondre aux besoins du projet.

## Architecture MVC

**Appréhender un projet par couche via le concept d'architecture MVC (Model View Controller)**

L'architecture MVC est un modèle de conception qui sépare une application en trois composants principaux : le modèle, la vue et le contrôleur. Cette séparation aide à organiser le code, le rend plus modulaire, et facilite la maintenance et l'évolutivité des applications.

- **Modèle (Model)** : Représente la structure des données. Le modèle gère les règles d'affaires, les requêtes de base de données, et les opérations de données.
- **Vue (View)** : Présente les données à l'utilisateur. La vue est la représentation visuelle des données, généralement les interfaces utilisateur.
- **Contrôleur (Controller)** : Agit comme un intermédiaire entre le modèle et la vue. Le contrôleur traite les entrées de l'utilisateur, manipule les données à travers le modèle et renvoie la sortie à la vue.

## POO et best practices

**Faire un projet "full POO" et dans les "best practices" attendu par les entreprises**

La programmation orientée objet (POO) est une méthode de programmation qui utilise des classes et des objets. Elle permet de structurer le code de manière à ce qu'il soit plus facile à comprendre, à maintenir et à réutiliser.

**Orchestrer via un gestionnaire de dépendance (Composer)**

Composer est un outil de gestion de dépendances pour PHP. Il permet de déclarer les bibliothèques dont dépend votre projet et de les gérer (installer/mettre à jour).

- Utilisez le fichier `composer.json` pour déclarer les dépendances.
- Exécutez `composer install` pour installer les dépendances spécifiées.
- Utilisez l'autoloading PSR-4 pour charger automatiquement vos classes sans nécessiter de `require` ou `include` manuels.
- Analyser régulièrement les dépendances pour s'assurer qu'elles sont à jour et sécurisées.
- Comment choisir des dépendances de qualité et maintenues.

**Développement de test unitaire (PHPUnit)**

PHPUnit est un framework de test unitaire pour PHP. Les tests unitaires permettent de tester des parties isolées du code pour s'assurer qu'elles fonctionnent comme prévu.

- Écrivez des tests qui couvrent à la fois les cas d'utilisation normaux et les cas limites.
- Exécutez régulièrement les tests pour garantir que les modifications n'introduisent pas de régressions.
- Intégrez les tests unitaires dans un pipeline CI pour automatiser les tests.

## DevOps

**Option DevOps (Docker, Deployer ...)**

L'introduction au DevOps et aux outils comme Docker et Deployer aide les étudiants à comprendre l'importance de l'automatisation et de la gestion de l'infrastructure comme code.

- **Docker** : Enseignez la création de conteneurs pour les environnements de développement et de production, facilitant ainsi le déploiement et la scalabilité.
- **Deployer** : Montrez comment automatiser le déploiement des applications sur différents serveurs. (via des pipeline CI/CD)

Ces objectifs pédagogiques fournissent une base solide pour les étudiants en code applicatif et architecture applicative, les préparant pour les défis du développement moderne et les attentes du monde professionnel.


!!! warning "Information importante"

    **Ce framework n'est pas adapté à une utilisation en production. Il est destiné à des fins pédagogiques.**

