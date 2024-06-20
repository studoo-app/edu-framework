# L'arborescence du projet

L'arborescence du projet est la structure de votre projet. Elle est composée de dossiers et de fichiers. Cette structure est importante pour le bon fonctionnement du projet.

## Voici l'arborescence du projet :

```
├── app
│   ├── Config
│   │   ├── commands.yaml
│   │   └── routes.yaml
│   ├── Controller
│   └── Template
│       ├── base.html.twig
│       └── error
│           ├── http-404.html.twig
│           ├── http-405.html.twig
│           └── http-Default.html.twig
├── bin
│   └── edu (Ligne de commande)
├── public
│   └── index.php
├── vendor
│   └── [...] (Librairies)
├── .env
├── .env.example
├── .gitignore
├── compose.json
├── composer.json
├── README.md
└── LICENSE
```

## Détail de l'arborescence

Voici le détail de l'arborescence du projet :

| Dossier / Fichier                             | Description                                                            |
|-----------------------------------------------|------------------------------------------------------------------------|
| **app**                                       | Dossier contenant les fichiers de l'application                        |
| **app/Config**                                | Dossier contenant les fichiers de configuration                        |
| **app/Config/commands.yaml**                  | Fichier de configuration des commands                                  |
| **app/Config/routes.yaml**                    | Fichier de configuration des routes                                    |
| **app/Controller**                            | Dossier contenant les classes "Controllers"                            | 
| **app/Command**                               | Dossier contenant les classes "Command"                                | 
| **app/Template**                              | Dossier contenant les fichiers des templates                           |
| **app/Template/base.html.twig**               | Fichier du template de base                                            | 
| **app/Template/error**                        | Dossier contenant les fichiers du template des erreurs                 |
| **app/Template/error/http-404.html.twig**     | Fichier du template de la page d'erreur 404                            |
| **app/Template/error/http-405.html.twig**     | Fichier du template de la page d'erreur 405                            |
| **app/Template/error/http-Default.html.twig** | Fichier du template de la page d'erreur par défaut                     |
| **bin/edu**                                   | Dossier de la ligne de commande Edu                                    |
| **public**                                    | Dossier contenant les fichiers publics                                 |
| **public/index.php**                          | Fichier de démarrage de l'application (Endpoint)                       |
| **vendor**                                    | Dossier contenant les librairies                                       |
| **.env**                                      | Fichier de configuration de l'application                              |
| **.env.example**                              | Fichier d'exemple de configuration de l'application                    |
| **.gitignore**                                | Fichier de configuration de GIT, pour exclure des fichiers ou dossiers |
| **compose.yaml**                              | Fichiers de configuration de Docker                                    |
| **composer.json**                             | Fichier de configuration de Composer                                   |
| **README.md**                                 | Fichier de description du projet                                       |
| **LICENSE**                                   | Fichier de licence du projet                                           |

