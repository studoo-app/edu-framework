# Migrer de la version v2.2 à v2.3

## Introduction
La version 2.3 du framework apporte son lot de nouveautés : barre de debug, profiler, enregistrement des requêtes en SQLite et nouveau prérequis PHP. \
Je vous invite à lire attentivement les changements apportés pour migrer votre projet de la version 2.2 à la version 2.3.
Lien sur la release note : [Release note 2.3](../changelog.md#v230-05102026)

## Comment migrer ?
Pour migrer de la version 2.2 à la version 2.3, vous devez suivre les étapes suivantes :

1. **Analyser les changements** : Analysez les changements apportés par la nouvelle version pour comprendre leur impact sur votre projet.
2. **Sauvegarder vos données** : Assurez-vous de sauvegarder vos données avant de procéder à la migration pour éviter toute perte.
3. Faire les actions suivantes :

3.1 **Mettre à jour votre version de PHP** :

!!! warning "Version obligatoire"

    À partir de la version **v2.3**, le framework nécessite **PHP 8.4** minimum (la v2.2 requérait PHP 8.2).
    Nous vous recommandons d'installer la version 8.4 ou plus (8.5).

| | Version minimum | Recommandée |
|--------|-------------|-----------------|
| v2.2   | PHP 8.2     | 8.2 ou plus     |
| v2.3   | **PHP 8.4** | 8.4 ou plus (8.5) |

Pour installer ou mettre à jour PHP, suivre le chapitre ["Environnement de développement"](../installation/prerequis.md#environnement-de-developpement).

3.2 **Vérifier les extensions PHP** :

La barre de debug et le profiler utilisent **SQLite** pour enregistrer les requêtes HTTP reçues par le serveur.
Vérifiez que l'extension `pdo_sqlite` est active (en plus de `openssl`, `mbstring` et `pdo_mysql`) :

```Bash
php -m | grep -i sqlite
```

!!! note "Résultat attendu"

    ```Bash
    pdo_sqlite
    sqlite3
    ```

Si l'extension n'est pas active, voir le chapitre ["Activation des extensions"](../installation/prerequis.md#activation-des-extensions).

3.3 **Mettre à jour le fichier composer** :

```diff
  "require": {
--    "php": ">=8.2",
++    "php": ">=8.4",
     "ext-mbstring": "*",
     "studoo/edu-framework": "2.x-dev"
  },
```

3.4 **Mettre à jour le framework** :
```Bash
composer update studoo/edu-framework
```

Résultat :
```bash
[...]
Package operations: 0 installs, 1 update, 0 removals
  - Upgrading studoo/edu-framework (2.2.x-dev xxxxxxx => 2.3.x-dev xxxxxxx): Extracting archive
[...]
```

3.5 **Version le framework** :
```Bash
 php bin/edu --version
```

Résultat :
```bash
EduFramework v2.3.x-xxxx
```

## Les changements

### **Sécurité des dépendances**
La dépendance `symfony/yaml` passe en version **6.4** pour corriger trois failles de sécurité connues en 6.3 :

- CVE-2026-45304 : allocation mémoire exponentielle via expansion récursive d'alias ("Billion Laughs")
- CVE-2026-45305 : ReDoS via backtracking catastrophique dans `Parser::cleanup()`
- CVE-2026-45133 : épuisement de la pile via récursion non bornée

La mise à jour via `composer update studoo/edu-framework` applique automatiquement ce correctif, aucune action supplémentaire n'est nécessaire.

### **Les codes HTTP des pages d'erreur**
Les controllers d'erreur du framework (`HttpError404Controller`, `HttpError405Controller`, ...) envoient maintenant le **code de statut HTTP correspondant** (`404`, `405`, ...) au lieu de `200`. \
Si votre projet dispose de tests unitaires vérifiant le rendu ou le statut des pages d'erreur, ils peuvent nécessiter une mise à jour.

### **Les routes du mode debug**
Les routes `/_debug/profiler` et `/edu-logs` ne sont enregistrées **qu'en mode développement** (`APP_ENV=dev`). \
Elles ne sont donc plus accessibles lorsque `APP_ENV=prod`. Si votre application déclarait ses propres routes avec ces URI, elles sont maintenant pleinement disponibles en mode production.

## Les nouveautés

### **La barre de debug et le profiler**
En mode développement (`APP_ENV=dev`), une **barre de debug** s'affiche en bas de chaque page (statut HTTP, route, temps d'exécution, mémoire, base de données, ...) et une **page de profiler** permet de naviguer dans l'historique des requêtes HTTP.

Pour plus de détails, consulter la page [La barre de debug et le profiler](../boost/debug-bar.md).

### **Les options de la commande start**
La commande `start` accepte de nouvelles options pour adapter le démarrage à vos besoins :

```bash
php bin/edu start --port=8080
```

| Option            | Description                                                           |
|-------------------|-----------------------------------------------------------------------|
| `-p` ou `--port`  | Change le port d'écoute du serveur de développement (défaut : `8042`) |
| `--no-start`      | Vérifie les prérequis de votre environnement sans démarrer le serveur  |

Pour plus de détails, consulter la page [Start/Stop l'application](../installation/start-application.md).

### **Le service MailPit**
Le service de mail **MailPit** remplace Mailcatcher. Il fait office de serveur SMTP (port `1025`) pour tester l'envoi de mails depuis votre application, et propose une interface web (port `8025`) pour consulter les mails envoyés.

La variable d'environnement `MAILER_DSN` de votre fichier `.env` doit pointer vers ce service :

```dotenv
MAILER_DSN="smtp://localhost:1025"
```

### **Le service dbgate**
Le service [dbgate](https://dbgate.org){:target="_blank"} est ajouté au fichier `compose.yaml` pour administrer vos bases de données (MySQL et SQLite) depuis une interface web, en complément de PHPMyAdmin.

Pour récupérer le nouveau fichier `compose.yaml` :

=== ":fontawesome-brands-windows: WINDOWS"

    Ouvrir un terminal git bash à la racine de votre projet

    Pour télécharger le fichier compose, suivre les instructions :

    ```bash
    curl -o compose.yaml https://raw.githubusercontent.com/studoo-app/edu-framework/2.x/compose.yaml
    ```

    !!! bug "curl: (35)"

        Si vous rencontrez une erreur `curl: (35) schannel: next InitializeSecurityContext failed: Unknown error (0x80092012) - The revocation function was unable to check revocation for the certificate.`

        Saisir la commande suivante :

        ```bash
        curl --ssl-no-revoke -o compose.yaml https://raw.githubusercontent.com/studoo-app/edu-framework/2.x/compose.yaml
        ```

=== ":fontawesome-brands-apple: MAC OS"

    Ouvrir un terminal à la racine de votre projet

    Pour télécharger le fichier compose, suivre les instructions :

    ```bash
    curl -sS https://raw.githubusercontent.com/studoo-app/edu-framework/2.x/compose.yaml -o compose.yaml
    ```

Puis redémarrer les services :

```Bash
docker compose up -d
```

Pour plus de détails, consulter la page [Comment installer et gérer les services](../installation/start-services.md).
