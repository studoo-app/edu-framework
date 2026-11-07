# Installing Edu Framework

The installation is done on the command line through a terminal (git-bash, iterm, warp, tabby ...)

## Choose your project location
Use the "cd" command to navigate through your file tree and select the location of your project.

```Bash
cd NOM_DU_DOSSIER // to move forward in your file tree
cd .. // to move back
pwd // to find out where you are in your file tree
```

## Creating the project

We are going to create the project with the "composer" package manager. This step lets you build the base of the project with a single command line.

Type this command in your terminal:

!!! info "Important information"

    Replace **NOM_DU_PROJET** with the name of your project

````Bash
composer create-project studoo/edu-framework-skeleton NOM_DU_PROJET
````

!!! warning "Project installation error"

    Be careful, this command may fail if you have not installed PHP.
    
    See the chapter ["Project installation error"](#dependency-installation-error)


!!! note "Example of the output of the command"

    This output is an example and will probably not be exactly yours.

    ````Bash
    Creating a "studoo/edu-framework-skeleton" project at "./NOM_DU_PROJET"
    Installing studoo/edu-framework-skeleton (X.0.0)
      - Downloading studoo/edu-framework-skeleton (X.0.0)
      - Installing studoo/edu-framework-skeleton (X.0.0): Extracting archive
    Created project in /Users/redbull/Projects/studoo/NOM_DU_PROJET
    Loading composer repositories with package information
    Updating dependencies
    Lock file operations: 9 installs, 0 updates, 0 removals
      - Locking graham-campbell/result-type (X.1.x-dev 60c5f57)
        [...]
      - Locking vlucas/phpdotenv (dev-master 1a7ea2a)
    Writing lock file
    Installing dependencies from lock file (including require-dev)
    Package operations: 9 installs, 0 updates, 0 removals
      - Installing symfony/polyfill-php80 (X.x-dev 6caa573): Extracting archive
        [...]
      - Installing studoo/edu-framework (dev-main c5cd9c5): Extracting archive
    Generating autoload files
    7 packages you are using are looking for funding.
    Use the `composer fund` command to find out more!
    No security vulnerability advisories found
    ````

It is important to know the installation steps. This will help you understand what is going on if the installation fails.


1. **Creating "studoo/edu-framework-skeleton"** 
Creation of a temporary folder 
2. **Installing "studoo/edu-framework-skeleton"** 
Download and installation of the edu-framework skeleton 
3. **Created project**
Creation of your project folder in your file tree 
4. **Loading composer** 
Reading of the "require" section (list of libraries) of your composer.json file 
5. **Updating dependencie** 
Analysis of the library (package) dependencies 
6. **Writing lock file**
Writing of the composer.lock file to lock the library versions. 
7. **Installing dependencie** 
Installation of the "vendor" folder. This is the folder where all the libraries are installed 
8. **Generating autoload files** 
Writing of the "/vendor/autoload.php" file, which includes all the libraries. 
9. **[...]**
Result of all the operations. Warning messages about updates may be displayed in this section. 


### Project installation error

If you run into errors while installing the project, here are a few explanations to help you:

````Bash
The openssl extension is required for SSL/TLS protection but is not availab
  le. If you can not enable the openssl extension, you can disable this error
  , at your own risk, by setting the 'disable-tls' option to true.
````

See the chapter ["Enable the OpenSSL extension"](prerequis.md#development-environment)

````Bash
Cannot use studoo/edu-framework-skeleton's latest version v1.3.0 as it requires ext-mbstring * which is missing from your platform.
 
In CreateProjectCommand.php line 421:
 
  Could not find package studoo/edu-framework-skeleton with stability stable in a version installa
  ble using your PHP version, PHP extensions and Composer version.
````

See the chapter ["Enable the mbstring extension"](prerequis.md#development-environment)

## Initialize your project

The project has just been installed; the next step is to initialize the configuration files. 

You need to go into your project with the "cd" command

!!! warning "Project name"

    Replace **NOM_DU_PROJET** with the name of your project

````Bash 
cd NOM_DU_PROJET
````

Then type this command in your terminal:

````Bash 
composer edu:init
````

!!! info ".env file"

    A .env file will be created at the root of your project. It contains all the environment variables of your project.

### Startup error

If you start your application server **without the ".env" file** (Dotenv), here is the error message:

````Bash
Fatal error: Uncaught Dotenv\Exception\InvalidPathException: 
Unable to read any of the environment file(s) at [...]
````

### Routes file error

If the routes file **"app/Config/routes.yaml"** cannot be found, the framework throws an `ErrorRouteConfigNotExistException` exception:

````Bash
Fatal error: Uncaught Studoo\EduFramework\Core\Exception\ErrorRouteConfigNotExistException: 
Le fichier de configuration des routes <.../app/config/routes.yaml> n'existe pas. 
Vérifiez la configuration 'route_config_path' et la casse du dossier 
(Exemple: app/Config et non app/config, les systèmes de fichiers Linux sont sensibles à la casse)
````

Possible causes:

- the `route_config_path` setting in "public/index.php" does not point to the right folder,
- **the case of the folder name is wrong**: the folder must be named `app/Config` (capital C) and not `app/config`.

!!! warning "Case sensitivity"

    On macOS and Windows, the case of file and folder names is ignored: a project with `app/config` works locally.
    However, on Linux (GitHub Codespaces, devcontainer, production server), the file system is **case-sensitive**: `app/config` and `app/Config` are two different folders.

### Dependency installation error

When running the "composer create-project studoo/edu-framework-skeleton ..." command, you may run into dependency installation errors. 

Here is an example of an error message:

````Bash
Problem 1
- Root composer.json requires studoo/edu-framework dev-main, found studoo/edu-framework[dev-dev, v0.2.1, ..., v0.6.0, v1.0.0, ..., 1.4.x-dev] but it does not match the constraint.
````

To solve this problem, you have several options:
- delete the "composer.lock" file and run the "composer install" command again
- update the version of your project in the "composer.json" file and run the "composer install" command again

In the composer.json file, change the following line:

```diff
"require": {
-     "studoo/edu-framework": "dev-main"
+     "studoo/edu-framework": "2.x-dev"
}
```














