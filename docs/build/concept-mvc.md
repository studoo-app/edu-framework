# Le MVC, c'est quoi ?

Edu Framework utilise le modèle MVC pour organiser et structurer les applications web.

MVC (Model-View-Controller) est l'un des modèles (software design pattern) les plus anciens et les plus utilisés. 

Il sépare les préoccupations en trois composants - le modèle contient les données et la logique métier, la vue affiche l'interface utilisateur et le contrôleur gère l'entrée de l'utilisateur et coordonne le modèle et la vue.

MVC facilite la maintenance du code en découplant l'interface utilisateur de la logique métier.

## Contrôleur (Controller)

Le contrôleur est l'intermédiaire entre le modèle et la vue. Il gère les événements utilisateur, effectue les traitements nécessaires et coordonne les mises à jour entre le modèle et la vue.

Le contrôleur interprète les actions de l'utilisateur, manipule les données du modèle en conséquence et met à jour la vue pour refléter les changements.

- **Interaction :** Le contrôleur interagit avec la vue en recevant les événements utilisateur et en mettant à jour la vue avec les nouvelles données provenant du modèle. Il interagit avec le modèle en appelant les méthodes appropriées pour manipuler les données et récupérer les informations nécessaires à l'affichage dans la vue.
- **Technique :** Le contrôleur est souvent associé à un système de routage pour gérer les requêtes HTTP et appeler les méthodes appropriées en fonction de l'URL demandée.
- **Mots clé :** Request HTTP, GET, POST

## Modèle (Model)

Le modèle représente les données et la logique métier de l'application. Il se charge de gérer les données, leur structure, leur cohérence et leurs relations. 

Il encapsule les règles métier, les opérations de lecture et d'écriture dans la base de données ou tout autre système de stockage, et fournit une interface pour interagir avec ces données.

Le modèle est indépendant de l'interface utilisateur et des mécanismes de contrôle.

- **Interaction :** Le modèle interagit avec la couche de contrôleur en fournissant des méthodes permettant au contrôleur de manipuler les données et de récupérer les informations nécessaires à l'affichage dans la vue. 
- **Technique :** La couche modèle est souvent associée à un ORM (Object-Relational Mapping) pour la gestion du CRUD (Create, Read, Update, Delete)
- **Mots clé :** logique métier, data, ORM, CRUD

## Vue (View)

La vue est la partie de l'application qui gère l'affichage des données à l'utilisateur. Elle se charge de la présentation et de la mise en forme des informations provenant du modèle. 

La vue est passive, ce qui signifie qu'elle ne traite pas les événements utilisateur ni ne modifie les données directement. Elle se contente d'afficher les données et de transmettre les actions de l'utilisateur au contrôleur.

- **Interaction :** La vue interagit avec le contrôleur et récupère également les données du modèle via le contrôleur pour les afficher à l'écran. 
- **Technique :** La couche Vue est souvent associée à un moteur de Template (Template engine) pour la gestion et la flexibilité des formats d'affichage. 
- **Mots clé :** Moteur de template

!!! info "Moteur de template TWIG"

    Edu Frame utilise le moteur de template TWIG : [https://twig.symfony.com](https://twig.symfony.com){:target="_blank"}


# Pourquoi utilisé le MVC

Le modèle MVC (Modèle-Vue-Contrôleur) est utilisé pour plusieurs raisons, qui découlent principalement de sa conception basée sur **la séparation des préoccupations (SoC, Separation of Concerns)**. 

Voici quelques avantages clés de l'utilisation du modèle MVC :

1. **Facilité de maintenance :** En séparant les différents aspects de l'application (données, présentation, et contrôle), il est plus facile de localiser et de corriger les problèmes, d'apporter des modifications ou d'ajouter de nouvelles fonctionnalités. Les développeurs peuvent travailler sur des parties spécifiques de l'application sans affecter les autres.
2. **Réutilisabilité du code :** La séparation des préoccupations permet de créer des composants modulaires et réutilisables, ce qui réduit la duplication du code et facilite le développement d'applications plus complexes.
3. **Amélioration de la collaboration :** Dans les projets impliquant plusieurs développeurs, la séparation des préoccupations facilite la collaboration. Les développeurs peuvent travailler en parallèle sur différents aspects de l'application, tels que la logique métier, l'interface utilisateur et la gestion des événements, sans interférer les uns avec les autres.
4. **Flexibilité et évolutivité :** Le modèle MVC permet de modifier ou d'étendre facilement les différentes parties de l'application sans affecter le reste. Par exemple, il est possible de changer l'interface utilisateur sans modifier la logique métier ou la gestion des données.
5. **Meilleure organisation du code :** Le modèle MVC impose une structure claire et bien organisée au code de l'application, ce qui facilite la compréhension et la navigation dans le code pour les développeurs.
6. **Testabilité :** La séparation des préoccupations facilite l'écriture de tests unitaires et d'intégration pour les différentes parties de l'application, car elles sont moins dépendantes les unes des autres.
