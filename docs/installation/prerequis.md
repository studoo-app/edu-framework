# Avant de démarrer

Avant l'installation du framework vous devez avoir sur votre poste de travail les composants suivants

## Liste des composants nécessaires

Voici la liste des composants nécessaires pour assurer le bon déroulement du projet. Pour chaque composant, nous avons réalisé une page d'aide à l'installation et à l'utilisation :

- [ ] [:simple-homebrew: Gestionnaire de paquets](prerequis.md#gestionnaire-de-paquets)
- [ ] [:simple-git: Gestionnaire des versions](prerequis.md#gestionnaire-des-versions)
- [ ] [:simple-php: Environnement de développement](prerequis.md#environnement-de-developpement) 
    * [ ] OpenSSL extension
    * [ ] mbstring extension
    * [ ] pdo_mysql extension
- [ ] [:simple-composer: Gestionnaire de dépendance](prerequis.md#gestionnaire-de-dependance)
- [ ] [:simple-curl: Commande CURL](prerequis.md#commande-curl) 
- [ ] [:simple-docker: Environnement local](prerequis.md#environnement-local) 

___

## :simple-homebrew: Gestionnaire de paquets

Un poste de développeur (ou toute personne qui travaille dans l'informatique), se doit de bien maitriser son poste de travail. (appelé aussi "setup")

Pour faciliter les installations et la maintenance de votre setup, nous vous recommandons d'utiliser un "gestionnaire de paquets"

**"Gestionnaire de paquets"** (système) installe les programmes que vous connaissez et aimez à partir de la ligne de commande (CLI) avec un minimum de friction. Il :

- Élimine les fenêtres contextuelles de permission
- Masque les installations de type assistant GUI
- Évite la pollution PATH de l'installation de nombreux programmes
- Évite les effets secondaires inattendus de l'installation et de la désinstallation de programmes
- Trouve et installe automatiquement les dépendances
- Effectue toutes les étapes de configuration supplémentaires pour obtenir un programme fonctionnel

**Installation**

=== ":fontawesome-brands-windows: WINDOWS"

    !!! warning "Information importante"
    
        Ne pas ouvrir le PowerShell en mode administrateur

    Pour installer "Scoop", suivre les instructions :

    Ouvrir un terminal PowerShell et taper les commandes suivantes :
    ```bash
    Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
    ```
    ```bash
    Invoke-RestMethod -Uri https://get.scoop.sh | Invoke-Expression
    ```

    Pour plus informations, visitez le site [https://scoop.sh](https://scoop.sh)

=== ":fontawesome-brands-apple: MAC OS"

    !!! warning "Information importante"
    
        L'installation de Xcode Command Line tools d'Apple peut être nécessaire.

        Tapez la commande dans un terminal `xcode-select --install`

    Pour installer "Brew", suivre les instructions dans le "Installer Homebrew" :
    
    ```bash
    /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
    ```

    Pour plus informations, visitez le site [https://brew.sh](https://brew.sh)

___

## :simple-git: Gestionnaire des versions

Nous allons utiliser un système de contrôle de version distribué appelé "GIT"

Il permet de suivre les modifications apportées à un ensemble de fichiers au fil du temps, ce qui facilite la collaboration entre les développeurs.

Il offre également des fonctionnalités avancées telles que la possibilité de revenir à des versions antérieures de fichiers, de gérer les conflits de fusion et de collaborer avec d'autres développeurs.

Pour savoir sur GIT est installé sur votre poste de travail, tapez cette commande dans un terminal

````Bash
git --version
````

!!! note "Résultat attendu"

    Ce résultat est un exemple et il ne sera probablement pas le vôtre.

    ````Bash
    git version 2.39.2 (Apple Git-143)
    ````

**Installation**

=== ":fontawesome-brands-windows: WINDOWS"

     Pour installation GIT, ouvrez un terminal (PowerShell) pour installer le bucket :

    ```bash
    scoop bucket add main
    ```
    ```bash
    scoop install main/git
    ```

    Pour plus informations, visitez le site [https://scoop.sh/#/apps?q=git](https://scoop.sh/#/apps?q=git&id=95a0a6fa98a17215837dbe3e38b75262d6d4d7ae)

=== ":fontawesome-brands-apple: MAC OS"

    Pour installation GIT, ouvrez un terminal
    
    ```bash
    brew install git
    ```

    Pour plus informations, visitez le site [https://formulae.brew.sh/formula/git](https://formulae.brew.sh/formula/git#default)

___

## :simple-php: Environnement de développement

Ce framework utilise le langage PHP, plus information sur [https://www.php.net](https://www.php.net)

Pour savoir si PHP est installé sur votre machine, ouvrez un terminal et saisir cette commande :

````Bash
php -v
````

!!! note "Résultat attendu"

    Ce résultat est un exemple et il ne sera probablement pas le vôtre.

    ````Bash
    PHP 8.1.21 (cli) (built: Jul  6 2023 16:08:36) (NTS)
    Copyright (c) The PHP Group
    Zend Engine v4.1.21, Copyright (c) Zend Technologies
    with Zend OPcache v8.1.21, Copyright (c), by Zend Technologies
    ````

**Installation**

!!! warning "Version obligatoire"

    Le framework est construit à partir de la version PHP 8.1, nous vous recommendons d'installer la version 8.2 ou plus (8.3)

=== ":fontawesome-brands-windows: WINDOWS"

     Pour installation PHP, ouvrez un terminal (PowerShell) pour installer le bucket :

    ```bash
    scoop bucket add versions
    ```
    ```bash
    scoop install versions/php82
    ```

    Pour plus informations, visitez le site [https://scoop.sh/#/apps?q=php](https://scoop.sh/#/apps?q=php&id=5101819badef2a2c45455bdb63c0036655741250)

=== ":fontawesome-brands-apple: MAC OS"

    Pour installation PHP, ouvrez un terminal
    
    ```bash
    brew install php@8.2
    ```

    Pour plus informations, visitez le site [https://formulae.brew.sh/formula/php](https://formulae.brew.sh/formula/php#default)

### Activation des extensions

#### Ou se trouve votre fichier php.ini
Pour savoir ou se trouve votre fichier php.ini, vous pouvez utiliser la commande suivante :

````Bash
php --ini
````
Vous aurez un résultat similaire à celui-ci :

````Bash
Configuration File (php.ini) Path: /usr/local/etc/php/8.2
Loaded Configuration File:         /usr/local/etc/php/8.2/php.ini
Scan for additional .ini files in: /usr/local/etc/php/8.2/conf.d 
Additional .ini files parsed:      /usr/local/etc/php/8.2/conf.d/ext-opcache.ini
````
Dans cet exemple, le fichier php.ini se trouve dans le dossier "/usr/local/etc/php/8.2". 

#### OpenSSL extension
Pour cela, vous devez ouvrir votre fichier php.ini et décommenter la ligne suivante :

````text title="php.ini"
;extension=openssl
````
Enlever le point-virgule pour activer l'extension.

#### mbstring extension
Pour cela, vous devez ouvrir votre fichier php.ini et décommenter la ligne suivante :

````Bash
;extension=mbstring
````
Enlever le point-virgule pour activer l'extension.

#### pdo_mysql extension
Pour cela, vous devez ouvrir votre fichier php.ini et décommenter la ligne suivante :

````Bash
;extension=pdo_mysql
````
Enlever le point-virgule pour activer l'extension.

___

## :simple-composer: Gestionnaire de dépendance

Pour gérer les installations des libraries ou des packages dans le projet, nous utilisons un gestionnaire de dépendance appelé "COMPOSER".

Pour savoir si COMPOSER est installé sur votre machine, ouvrez un terminal et saisir cette commande :

```Bash
composer --version
```

!!! note "Résultat attendu"

    Ce résultat est un exemple et il ne sera probablement pas le vôtre.

    ````Bash
    Composer version 2.5.8 
    ````

**Installation**

=== ":fontawesome-brands-windows: WINDOWS"

     Pour installation COMPOSER, ouvrez un terminal (PowerShell) pour installer le bucket :

    ```bash
    scoop bucket add main
    ```
    ```bash
    scoop install main/composer
    ```

    Pour plus informations, visitez le site [https://scoop.sh/#/apps?q=composer](https://scoop.sh/#/apps?q=composer&id=9ce50f006acbb8649de41c1a38794e190a8c4453)

=== ":fontawesome-brands-apple: MAC OS"

    Pour installation COMPOSER, ouvrez un terminal
    
    ```bash
    brew install composer
    ```

    Pour plus informations, visitez le site [https://formulae.brew.sh/formula/composer](https://formulae.brew.sh/formula/composer#default)

___

## :simple-curl: Commande CURL

Souvent utilisé pour les requêtes HTTP, CURL est un outil en ligne de commande pour transférer des données avec des URL.

Il est souvent utilisé pour tester des API, des scripts ou télécharger des fichiers.

Plus d'information sur [https://curl.se](https://curl.se)


```Bash
curl --version
```

!!! note "Résultat attendu"

    Ce résultat est un exemple et il ne sera probablement pas le vôtre.

    ````Bash
    curl 7.79.1 (x86_64-apple-darwin21.1.0) libcurl/7.79.1 (SecureTransport) LibreSSL/2.8.3 zlib/1.2.11 nghttp2/1.45.1
    Release-Date: 2021-09-22
    Protocols: dict file ftp ftps gopher http https imap imaps ldap ldaps pop3 pop3s rtsp smb smbs smtp smtps telnet tftp
    Features: alt-svc AsynchDNS GSS-API HTTP2 HTTPS-proxy IPv6 Kerberos Largefile libz MultiSSL NTLM NTLM_WB SPNEGO SSL UnixSockets
    ````

**Installation**


=== ":fontawesome-brands-windows: WINDOWS"

    Pour installer la commande "curl", suivre les instructions :

    ```bash
    scoop bucket add main
    ```
    ```bash
    scoop install main/curl
    ```

    Pour plus informations, visitez le site [https://scoop.sh/#/apps?q=curl](https://scoop.sh/#/apps?q=curl&id=09e3750a1f0a703b6de0f8fa4ca1263ef5d59ad3)

=== ":fontawesome-brands-apple: MAC OS"

    Pour installer la commande "curl", suivre les instructions :
    
    ```bash
    brew install curl
    ```

    Pour plus informations, visitez le site [https://formulae.brew.sh/formula/curl](https://formulae.brew.sh/formula/curl#default)

___

## :simple-docker: Environnement local

Pour émuler les services utilisés par le framework, comme MySQL par exemple, nous vous encourageons vivement à utiliser Docker.

Docker est une plateforme de virtualisation légère qui permet d'isoler et de gérer facilement des applications dans des conteneurs. En utilisant Docker, vous pouvez créer et exécuter des conteneurs pour les différents services nécessaires à votre framework, tels que MySQL, sans avoir à les installer directement sur votre machine. Cela vous permet de gagner du temps et de simplifier le processus de développement. Alors n'hésitez pas à utiliser Docker pour émuler vos services !

[https://www.docker.com](https://www.docker.com)

L'utilisation de DOCKER est optionnel. Néanmoins, Docker est un outil essentiel pour les développeurs modernes.

La framework est livré avec un fichier `compose.yml` qui vous permet de démarrer rapidement les services nécessaires à votre projet.
Pour plus d'informations, on en reparle dans la section "Démarrer les services".