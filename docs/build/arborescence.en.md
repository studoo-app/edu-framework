# The project directory structure

The project directory structure is the layout of your project. It is made up of folders and files. This structure matters for the project to work properly.

## Here is the project directory structure:

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
│   └── edu (Command line)
├── public
│   └── index.php
├── tests (Unit tests, optional)
│   └── bootstrap.php
├── var
│   ├── dbdata (MySQL service data)
│   ├── dbgate (dbgate service configuration)
│   ├── logs (SQLite database of the server logs)
│   └── sqlite (Application SQLite databases)
├── vendor
│   └── [...] (Libraries)
├── .env
├── .env.example
├── .gitignore
├── compose.yaml
├── composer.json
├── phpunit.xml (Test configuration, optional)
├── README.md
└── LICENSE
```

## Directory structure in detail

Here is the project directory structure in detail:

| Folder / File                                 | Description                                                            |
|-----------------------------------------------|------------------------------------------------------------------------|
| **app**                                       | Folder containing the application files                                |
| **app/Config**                                | Folder containing the configuration files                              |
| **app/Config/commands.yaml**                  | Command configuration file                                             |
| **app/Config/routes.yaml**                    | Route configuration file                                               |
| **app/Controller**                            | Folder containing the "Controller" classes                             | 
| **app/Command**                               | Folder containing the "Command" classes                                | 
| **app/Template**                              | Folder containing the template files                                   |
| **app/Template/base.html.twig**               | Base template file                                                     | 
| **app/Template/error**                        | Folder containing the error template files                             |
| **app/Template/error/http-404.html.twig**     | Template file for the 404 error page                                   |
| **app/Template/error/http-405.html.twig**     | Template file for the 405 error page                                   |
| **app/Template/error/http-Default.html.twig** | Template file for the default error page                               |
| **bin/edu**                                   | Edu command-line folder                                                |
| **public**                                    | Folder containing the public files                                     |
| **public/index.php**                          | Application startup file (Endpoint)                                    |
| **tests**                                     | [Unit tests](tests-unitaires.md) folder (PHPUnit)                      |
| **phpunit.xml**                               | PHPUnit configuration file                                             |
| **var**                                       | Folder containing variable data (databases, logs, configuration)       |
| **var/dbdata**                                | MySQL service data (Docker volume)                                     |
| **var/logs**                                  | SQLite database `core_logs.sqlite` of the development server logs      |
| **var/sqlite**                                | Application SQLite databases                                           |
| **vendor**                                    | Folder containing the libraries                                        |
| **.env**                                      | Application configuration file                                         |
| **.env.example**                              | Example application configuration file                                 |
| **.gitignore**                                | GIT configuration file, to exclude files or folders                    |
| **compose.yaml**                              | Docker configuration files                                             |
| **composer.json**                             | Composer configuration file                                            |
| **README.md**                                 | Project description file                                               |
| **LICENSE**                                   | Project license file                                                   |

