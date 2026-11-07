# Migrating from v1.2 to v2.0

## Introduction
Version 2.0 of the framework brings many improvements and bug fixes. \
Please read the changes carefully to migrate your project from version 1.2 to version 2.0.
Link to the release note: [Release note 2.0](../changelog.md#v200-06042024)

## How to migrate?
To migrate from version 1.2 to version 2.0, follow these steps:

1. **Analyze the changes**: Analyze the changes brought by the new version to understand their impact on your project.
2. **Back up your data**: Make sure you back up your data before migrating to avoid any loss.
3. Carry out the following actions: 

3.1 **Update the composer file**:
```diff
  "require": {
    "php": ">=8.1",
    "ext-mbstring": "*",
--    "studoo/edu-framework": "1.2.x-dev"
++    "studoo/edu-framework": "2.x-dev"
  },
```

3.2 **Update the framework**:
```Bash
composer update studoo/edu-framework
```

Result:
```bash
[...]
Package operations: 0 installs, 1 update, 0 removals
  - Downloading studoo/edu-framework (2.x-dev xxxxx)
  - Upgrading studoo/edu-framework (1.2.x-dev ef385b8 => 2.0.x-dev xxxxx): Extracting archive
[...]
```

3.3 **Check the framework version**:
```Bash
 php bin/edu --version
```

Result:
```bash
EduFramework v2.x-xxxx
```

## Changes
There are many changes between version 1.2 and version 2.0. \
Here are the main changes to take into account:

### **Docker**
In version 1.2, the services were started with the `composer edu:docker:db-service:start mysql` command. \
In version 2.0, the services are started with the `docker compose up -d` command.

Here are the actions to carry out to migrate from version 1.2 to version 2.0:

1. **Stop the services**: (if they are running)
```Bash
composer edu:docker:db-service:stop mysql
```

2. **Download the new Docker file**:

=== ":fontawesome-brands-windows: WINDOWS"

    Open a git bash terminal at the root of your project

    To download the compose file, follow the instructions:
    
    ```bash
    curl -o compose.yaml https://raw.githubusercontent.com/studoo-app/edu-framework/main/compose.yaml
    ```

    !!! bug "curl: (35)"

        If you get the error `curl: (35) schannel: next InitializeSecurityContext failed: Unknown error (0x80092012) - The revocation function was unable to check revocation for the certificate.`
    
        Type the following command:

        ```bash
        curl --ssl-no-revoke -o compose.yaml https://raw.githubusercontent.com/studoo-app/edu-framework/main/compose.yaml
        ```

=== ":fontawesome-brands-apple: MAC OS"

    Open a terminal at the root of your project

    To download the compose file, follow the instructions:

    ```bash
    curl -sS https://raw.githubusercontent.com/studoo-app/edu-framework/2.0/compose.yaml -o compose.yaml
    ```


3. **Edit the new Docker file**:

You can edit the `compose.yaml` file to adjust the settings to your environment.

```diff
    environment:
      MYSQL_DATABASE: app_db
--    MYSQL_ALLOW_EMPTY_PASSWORD: 'yes'
++    #MYSQL_ALLOW_EMPTY_PASSWORD: 'yes'
--    #MYSQL_ROOT_PASSWORD: root
++    MYSQL_ROOT_PASSWORD: root
      #MYSQL_USER: app_db_user
      #MYSQL_PASSWORD: app_db_password
```

!!! note

    **Note**: The comments are added to help you understand the changes.
    Make sure you follow the YAML syntax to avoid errors (indentation, etc.).

4. **Start the services**:

```Bash
docker compose up -d
```

5. **Delete the old Docker folders**:

!!! warning "Important information"

    **Warning**: Before deleting the old Docker folders, make sure the Docker services are running.
    Open a git bash terminal at the root of your project

```Bash
rm -fr docker
```

6. **Delete the script lines in composer**:

> Open the `composer.json` file and delete the following lines:

```diff
  "scripts": {
    "edu:start": [
      "Composer\\Config::disableProcessTimeout",
      "php -S localhost:8042 -t public"
    ],
    "edu:init": [
      "php -r \"file_exists('.env') || copy('.env.example', '.env');\""
    ],
--  "edu:docker:db-service:start": "Studoo\\EduFramework\\Scripts\\DockerPilot::start",
--  "edu:docker:db-service:down": "Studoo\\EduFramework\\Scripts\\DockerPilot::down"
  },
  "scripts-descriptions": {
    "edu:init": "Create env file",
    "edu:start": "Start local server",
--  "edu:docker:db-service:start": "Start docker related database service [args : mysql / maria-db]",
--  "edu:docker:db-service:down": "Stop and remove docker related database service [args : mysql / maria-db]"
  }
```

### **Debug bar**

The environment configuration file has been modified to be more readable and easier to understand.

Here is the list of variables:

 - The `APP_ENV` variable \
This variable enables debug features

| Value  | Description |
|--------|-------------|
| dev    | Development mode |
| prod   | Production mode |

Example:
```Bash
APP_ENV=dev
```

 - The `DB_TYPE` variable \
This variable defines the type of database

| Value   | Description             |
|---------|-------------------------|
| mysql   | MySQL database          |
| mariadb | MariaDB database        |

Example:
```Bash
DB_TYPE=mysql
```

## New features

### **New commands**
New commands have been added to make developing your application easier.

- `php bin/edu make:api`: To generate an API controller
- `php bin/edu make:command`: To generate a console command
