# List of errors

!!! warning "Common errors"
    This page lists common errors you might run into when installing or using the Edu Framework project.
    Other errors may occur, but those are covered in the documentation pages.
    We recommend using the search engine to find solutions to your problems.


## APP_ENV error: Warning: Undefined array key "APP_ENV"

This error comes from an undefined environment variable.
To fix this error, you must define the `APP_ENV` environment variable in the `.env` file at the root of your project.

Open the `.env` file at the root of your project and add the following line:

````yaml
## << Config application
APP_ENV=dev
## >> Config application
````

## Fatal error: Uncaught PDOException: SQLSTATE`[HY000] [2002]` Connection refused

This error can have several causes:

- Your database is not available (the service is not started)
  Start your database service (docker ...)

- You do not have the right login/password
  Open the `.env` file at the root of your project and check the following lines:

````yaml
## << Config Database
## to enable the database connection, set DB_HOST_STATUS=true
DB_HOST_STATUS=true
DB_HOST=127.0.0.1
DB_SOCKET=3306
DB_USER=root
DB_PASSWORD=root
DB_NAME=app_db
## >> Config Database
````

If you do not want to enable your database, you can disable it:

````yaml
## << Config Database
## to enable the database connection, set DB_HOST_STATUS=true
DB_HOST_STATUS=false
````

## If you cannot connect to your database

If you cannot connect to your database, you can check the following points:

- In the `docker/docker-compose-***.yml` file (replace *** with the version and database type), check that the `networks` configuration is set

````diff
   database:
    container_name: ${APP_NAME}-database
    image: mysql:5.7
    platform: linux/amd64
    ports:
      - "3306:3306"
    restart: always
    environment:
      #MYSQL_ALLOW_EMPTY_PASSWORD: 'yes'
      MYSQL_ROOT_PASSWORD: ${DB_PASSWORD}
      MYSQL_DATABASE: ${DB_NAME}
      #MYSQL_USER: app_db_user
      #MYSQL_PASSWORD: app_db_password
+   networks:
+     - dev
    volumes:
      - ../var/dbdata:/var/lib/mysql
  #PHP My Admin
  phpmyadmin:
    container_name: ${APP_NAME}-pma
    image: phpmyadmin:latest
    restart: always
    depends_on:
      - database
    ports:
      - "8081:80"
    environment:
-      PMA_HOST: db
+      PMA_HOST: database
    networks:
      - dev
````

!!! info "Warning"

    Be careful to respect the indentation and spacing in the docker-compose file

Remove your docker environment

````Shell
  docker-compose down
````
