# Construire avec Edu Framework

Cette section vous guide, étape par étape, de votre première page jusqu'à une application complète : formulaire, CRUD, API, commande CLI et tests. Chaque étape est l'occasion d'**apprendre une notion** que l'on retrouvera ensuite telle quelle dans un framework professionnel comme Symfony.

Les pages sont regroupées en quatre thèmes, avec pour chacune **ce que l'on construit** et **la notion travaillée**.

<div class="grid cards" markdown>

-   :material-sitemap: **Comprendre**

    ---

    Le concept d'architecture MVC et l'arborescence d'un projet Edu Framework, avant d'écrire la moindre ligne.

    [:octicons-arrow-right-24: Les fondations](#comprendre-les-fondations)

-   :material-hammer-wrench: **Construire**

    ---

    Une page, un formulaire protégé, le CRUD d'une entité, une API JSON et une commande CLI.

    [:octicons-arrow-right-24: Construire pas à pas](#construire-pas-a-pas)

-   :material-test-tube: **Tester**

    ---

    Vérifier automatiquement le bon fonctionnement de son code avec PHPUnit.

    [:octicons-arrow-right-24: Tester son application](#tester-son-application)

-   :material-book-open-variant: **Approfondir**

    ---

    La requête HTTP, les données, les routes, les erreurs, la barre de debug et le profiler.

    [:octicons-arrow-right-24: Approfondir](#approfondir-le-framework)

</div>

## Comprendre les fondations

**Avant de débuter, vous devez comprendre les points suivants :**

| Page | Ce que l'on apprend |
|------|---------------------|
| [Le concept d'architecture MVC](concept-mvc.md) | Séparer la logique (controller), les données (modèle) et l'affichage (vue), autour d'un cycle requête / réponse |
| [L'arborescence du projet](arborescence.md) | Savoir où se trouvent les controllers, les templates, la configuration et les fichiers publics |

## Construire pas à pas

Suivez les étapes dans l'ordre : chacune s'appuie sur la précédente.

| Étape | Ce que l'on construit | Ce que l'on apprend |
|-------|-----------------------|---------------------|
| 1. [Construire une page](controller-edu.md) | Un controller, sa route et son template Twig | Associer une URL à un controller, générer du HTML avec Twig |
| 2. [Construire un formulaire](controller-edu-post.md) | Un formulaire envoyé en méthode `POST` | Lire les données envoyées avec l'objet `Request` |
| 3. [Protéger un formulaire (CSRF)](csrf.md) | Un jeton CSRF dans le formulaire | Se protéger contre la falsification de requête inter-sites |
| 4. [Construire le CRUD](dataservice-select.md) | La liste, l'ajout, la modification et la suppression d'une ville | Entités, repositories et requêtes SQL préparées |
| 5. [Construire une API](controller-edu-api.md) | Une API JSON documentée au format OpenAPI | Codes de statut HTTP, format JSON, documentation d'API |
| 6. [Construire une commande CLI](new-command-edu.md) | Une nouvelle commande `bin/edu` | Automatiser une tâche en ligne de commande |

## Tester son application

| Page | Ce que l'on apprend |
|------|---------------------|
| [Tester son application (tests unitaires)](tests-unitaires.md) | Écrire et lancer des tests PHPUnit, tester plusieurs cas avec un DataProvider, tester un repository et un controller |

## Approfondir le framework

Après une première prise en main, ces pages détaillent les briques utilisées dans les étapes précédentes.

| Page | Ce que l'on apprend | Dans Symfony |
|------|---------------------|--------------|
| [Request - La gestion des requêtes HTTP](resquet.md) | Lire les paramètres, la méthode HTTP, les en-têtes et les fichiers envoyés | `Request` de HttpFoundation |
| [DatabaseService - La gestion des données](dataservice.md) | Se connecter à une base MySQL, MariaDB ou SQLite | Doctrine DBAL |
| [Route - Générer une URL par le nom](route.md) | Ne jamais écrire une URL « en dur » dans le code ou les templates | `path()`, `generateUrl()` |
| [Les erreurs HTTP](http-error.md) | Les codes 403, 404, 405 et 500, et la personnalisation des pages d'erreur | `HttpException`, templates d'erreur |
| [La barre de debug et le profiler](debug-bar.md) | Analyser une requête, ses requêtes SQL et ses performances | Web Debug Toolbar et Profiler |

!!! tip "Voir toutes les fonctionnalités"

    La page [Les fonctionnalités](../features.md) présente l'ensemble des briques du framework et leur équivalent dans Symfony.
