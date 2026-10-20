# Les fonctionnalités

Edu Framework reprend, en version simplifiée et lisible, les briques d'un framework professionnel comme Symfony. Chaque fonctionnalité est l'occasion d'**apprendre une notion** que l'on retrouvera ensuite telle quelle dans un framework du marché.

Cette page présente l'ensemble des fonctionnalités, regroupées par thème d'apprentissage, avec pour chacune **la notion travaillée** et **son équivalent dans Symfony**.

<div class="grid cards" markdown>

-   :material-sitemap: **Architecture MVC**

    ---

    Séparer le code en couches : controller, modèle et vue, autour d'un cycle requête / réponse.

    [:octicons-arrow-right-24: L'architecture](#architecture-et-cycle-dune-requete)

-   :material-database: **Données**

    ---

    Se connecter à une base, écrire des entités et des repositories, construire un CRUD.

    [:octicons-arrow-right-24: Les données](#les-donnees)

-   :material-api: **API**

    ---

    Renvoyer des données JSON avec le bon code HTTP et documenter son API au format OpenAPI.

    [:octicons-arrow-right-24: Les API](#les-api)

-   :material-console: **CLI**

    ---

    Utiliser `bin/edu` pour générer du code, et construire ses propres commandes en ligne de commande.

    [:octicons-arrow-right-24: La ligne de commande](#la-ligne-de-commande-et-les-generateurs)

-   :material-email-outline: **Mail**

    ---

    Tester l'envoi d'e-mails avec Mailpit : un serveur SMTP local et une boîte de réception web, sans envoyer de vrai message.

    [:octicons-arrow-right-24: Les mails](#les-mails)

-   :material-docker: **DevOps**

    ---

    Lancer la base, les outils et l'application dans Docker, comprendre les ports et les variables d'environnement.

    [:octicons-arrow-right-24: Le DevOps](#lenvironnement-et-le-devops)

-   :material-shield-lock: **Sécurité**

    ---

    Requêtes préparées, jeton CSRF, erreurs masquées en production : les bons réflexes contre les failles web.

    [:octicons-arrow-right-24: La sécurité](#la-securite)

-   :material-bug-check: **Débogage et performance**

    ---

    Barre de debug, profiler, requêtes SQL et profiler de code, comme dans un framework professionnel.

    [:octicons-arrow-right-24: Le débogage](#le-debogage-et-la-performance)

</div>

## Architecture et cycle d'une requête

| Fonctionnalité | Ce que l'on apprend | Dans Symfony |
|----------------|---------------------|--------------|
| **Point d'entrée unique** (`public/index.php`) | Toutes les requêtes passent par un seul fichier qui démarre le framework (*front controller*) | `public/index.php` |
| **Architecture MVC** | Séparer la logique (controller), les données (modèle) et l'affichage (vue) | Même architecture |
| **Autoloading PSR-4** (Composer) | Charger les classes sans `require`, organiser le code en *namespaces* | Même mécanisme |
| **Configuration par `.env`** | Séparer la configuration du code, distinguer les modes `dev` et `prod` (`APP_ENV`) | Fichier `.env`, `APP_ENV` |

Pour aller plus loin : [Le concept d'architecture MVC](build/concept-mvc.md) et [L'arborescence du projet](build/arborescence.md).

## Les routes et les controllers

| Fonctionnalité | Ce que l'on apprend | Dans Symfony |
|----------------|---------------------|--------------|
| **Routes en YAML** (`app/Config/routes.yaml`) | Associer une URL et une méthode HTTP à un controller | `config/routes.yaml` ou attribut `#[Route]` |
| **Paramètres de route** (`/user/{id:\d+}`) | Récupérer une partie de l'URL et la contraindre par une expression régulière | `{id}` avec `requirements` |
| **Un controller, plusieurs routes** (`Controller\VilleController::index`) | Regrouper les actions d'une même ressource dans une classe | Une méthode par action dans un controller |
| **Génération d'URL par le nom de la route** (`getNameToPath()`) | Ne jamais écrire une URL « en dur » dans le code ou les templates | `path()` dans Twig, `generateUrl()` |
| **L'objet `Request`** | Lire les paramètres, la méthode HTTP, les en-têtes et les fichiers envoyés | `Symfony\Component\HttpFoundation\Request` |
| **Téléversement de fichiers** (`hasFile()`, `getFile()`, `move()`) | Recevoir, valider et ranger un fichier envoyé par un formulaire | `UploadedFile` |

Pour aller plus loin : [Construire une page](build/controller-edu.md), [Request](boost/resquet.md) et [Générer une URL par le nom](boost/route.md).

## Les vues

| Fonctionnalité | Ce que l'on apprend | Dans Symfony |
|----------------|---------------------|--------------|
| **Templates Twig** (`app/Template`) | Séparer le HTML du PHP, utiliser l'héritage de templates et l'échappement automatique | Twig, à l'identique |
| **Cache des templates** (`var/cache/twig`) | Comprendre la compilation des templates et le rôle d'un cache | `var/cache` |
| **Fonction `dd()`** | Inspecter une variable pendant le développement | `dd()` / `dump()` |

Pour aller plus loin : [Construire une page](build/controller-edu.md) et [Construire un formulaire](build/controller-edu-post.md).

## Les données

| Fonctionnalité | Ce que l'on apprend | Dans Symfony |
|----------------|---------------------|--------------|
| **`DatabaseService`** (MySQL, MariaDB, SQLite) | Se connecter à une base avec PDO, changer de base par la configuration (`DB_TYPE`) | Doctrine DBAL |
| **Entités et repositories** | Représenter une table par une classe, isoler les requêtes SQL dans un repository | Doctrine ORM : `Entity` et `Repository` |
| **Le CRUD** | Créer, lire, modifier et supprimer des données, de la base jusqu'au formulaire | `make:crud` |
| **Le pattern PRG** (*Post/Redirect/Get*) | Rediriger après un formulaire pour éviter le double envoi | Même pattern (`redirectToRoute()`) |

Pour aller plus loin : [DatabaseService](boost/dataservice.md) et [Construire le CRUD](build/dataservice-select.md).

## Les API

| Fonctionnalité | Ce que l'on apprend | Dans Symfony |
|----------------|---------------------|--------------|
| **Controllers d'API JSON** | Renvoyer des données JSON avec le bon code HTTP | `JsonResponse` |
| **Documentation OpenAPI** (attributs `OpenApi\Attributes`) | Décrire une API pour qu'elle soit testable et partageable | NelmioApiDocBundle, API Platform |

Pour aller plus loin : [Construire une API](build/controller-edu-api.md).

## La ligne de commande et les générateurs

La commande `php bin/edu` regroupe les outils du développeur, comme `bin/console` dans Symfony.

| Commande | Ce qu'elle fait | Dans Symfony |
|----------|-----------------|--------------|
| `start` | Démarre le serveur de développement (options `--port`, `--no-start`) | `symfony server:start` |
| `check:config` | Vérifie les prérequis (version de PHP, extensions) | `symfony check:requirements` |
| `make:controller` | Génère un controller et son template | `make:controller` |
| `make:entity` | Génère une entité et son repository | `make:entity` |
| `make:api` | Génère un controller d'API documenté | `make:controller` + API Platform |
| `make:command` | Génère une commande CLI | `make:command` |
| `cache:clear` | Vide le cache de l'application | `cache:clear` |

Construire ses propres commandes permet aussi d'aborder l'automatisation, et l'usage d'un outil CLI par un agent IA.

Pour aller plus loin : [La commande bin/edu](installation/command-edu.md) et [Construire une commande CLI](build/new-command-edu.md).

## La sécurité

Edu Framework est utilisé dans des travaux pratiques de cybersécurité : volontairement simple et transparent, il permet de **comprendre une faille, puis la bonne pratique qui la corrige**.

| Fonctionnalité | La faille évitée | Dans Symfony |
|----------------|------------------|--------------|
| **Requêtes préparées** (PDO) | Injection SQL | Requêtes paramétrées de Doctrine |
| **Jeton CSRF** (`csrf_token()`, `isCsrfTokenValid()`) | Falsification de requête inter-sites (CSRF) | `csrf_token()` et `isCsrfTokenValid()`, mêmes noms |
| **Échappement Twig** | Injection de script (XSS) | Échappement automatique de Twig |
| **Messages d'erreur masqués en production** | Fuite d'informations techniques | Pages d'erreur de production |
| **Routes de debug uniquement en `dev`** | Exposition d'outils internes | Profiler désactivé en production |

Pour aller plus loin : [Protéger un formulaire (CSRF)](build/csrf.md) et [Les erreurs HTTP](boost/http-error.md).

## La gestion des erreurs

| Fonctionnalité | Ce que l'on apprend | Dans Symfony |
|----------------|---------------------|--------------|
| **Pages d'erreur personnalisables** (403, 404, 405, 500) | Les codes de statut HTTP et leur signification | Templates `error404.html.twig`, ... |
| **`ErrorHttpStatusException`** | Lever une erreur HTTP depuis un controller (Exemple : accès interdit) | `HttpException`, `createAccessDeniedException()` |
| **Message de l'exception en mode dev** | Lire une erreur pour la corriger, sans l'exposer en production | Page d'exception du mode dev |

Pour aller plus loin : [Les erreurs HTTP](boost/http-error.md).

## Le débogage et la performance

En mode développement (`APP_ENV=dev`), Edu Framework fournit des outils inspirés du *Web Debug Toolbar* et du *Profiler* de Symfony.

| Fonctionnalité | Ce que l'on apprend | Dans Symfony |
|----------------|---------------------|--------------|
| **Barre de debug** | Lire d'un coup d'œil le statut, la route, le temps et la mémoire d'une requête | Web Debug Toolbar |
| **Profiler et historique des requêtes** | Retrouver et filtrer les requêtes passées, avec leurs erreurs | Profiler (`/_profiler`) |
| **Requêtes SQL par requête HTTP** | Repérer une requête SQL lente ou répétée (problème « N+1 ») | Panneau Doctrine du profiler |
| **Profiler de code** (Excimer, flamegraph) | Mesurer le temps passé dans chaque fonction, lire un flamegraph | Blackfire, panneau *Performance* |

Pour aller plus loin : [La barre de debug et le profiler](boost/debug-bar.md).

## Les mails

Envoyer un e-mail depuis une application (confirmation d'inscription, mot de passe oublié, ...) se teste sans risquer d'écrire à de vraies personnes : les services Docker du framework incluent [Mailpit](https://github.com/axllent/mailpit){:target="_blank"}, qui **capture tous les mails envoyés**.

| Fonctionnalité | Ce que l'on apprend | Dans Symfony |
|----------------|---------------------|--------------|
| **Serveur SMTP local** (port `1025`) | Le protocole SMTP et la configuration d'un envoi par une variable d'environnement (`MAILER_DSN`) | `MAILER_DSN` du composant Mailer |
| **Boîte de réception web** ([http://localhost:8025](http://localhost:8025){:target="_blank"}) | Vérifier le contenu, le destinataire et la mise en forme d'un mail sans l'envoyer réellement | Mailpit, intégré à `symfony-docker` |
| **Accès depuis la barre de debug** (bloc DEV) | Retrouver les outils de développement à partir de l'application | Panneau *E-mails* du profiler |

```dotenv
MAILER_DSN="smtp://localhost:1025"
```

!!! info "Envoyer le mail"

    Le framework fournit le serveur de test, pas l'envoi lui-même : utilisez une bibliothèque d'envoi de mails comme [Symfony Mailer](https://symfony.com/doc/current/mailer.html){:target="_blank"}, configurée avec la variable `MAILER_DSN`.

Pour aller plus loin : [Démarrer les services](installation/start-services.md).

## L'environnement et le DevOps

| Fonctionnalité | Ce que l'on apprend | Dans Symfony |
|----------------|---------------------|--------------|
| **Services Docker** (MariaDB, Mailpit, phpMyAdmin, DbGate) | Lancer une base et des outils sans rien installer sur sa machine | Docker Compose de Symfony |
| **Application dans Docker** (FrankenPHP, HTTPS local) | Conteneuriser une application, comprendre les ports et les variables d'environnement | `symfony-docker` (FrankenPHP) |
| **Versionner son projet** (Git) | Suivre l'historique et travailler à plusieurs | Même outil |

Pour aller plus loin : [Démarrer les services](installation/start-services.md), [L'application dans Docker](installation/docker.md) et [Comment versionner son projet](installation/versionning.md).

## Le parcours d'apprentissage

La documentation est organisée comme un parcours progressif :

1. **[Démarrer](installation/index.md)** : installer l'environnement, créer et lancer son projet.
2. **[Construire](build/index.md)** : une page, un formulaire, le CRUD, une commande CLI, une API.
3. **[Consolider](boost/index.md)** : approfondir la requête, les données, les routes, les erreurs et le débogage.

Les objectifs pédagogiques de chaque étape sont détaillés dans la page [Objectifs pédagogiques](about.md).

!!! warning "Information importante"

    **Ce framework n'est pas adapté à une utilisation en production. Il est destiné à des fins pédagogiques.**
