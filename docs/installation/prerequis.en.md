# Before you start

Before installing the framework, you must have the following components on your workstation

## List of required components

Here is the list of components required for the project to run smoothly. For each component, we have written a page to help you install and use it:

- [ ] [:simple-homebrew: Package manager](prerequis.md#package-manager)
- [ ] [:simple-git: Version manager](prerequis.md#version-manager)
- [ ] [:simple-php: Development environment](prerequis.md#development-environment) 
    * [ ] OpenSSL extension
    * [ ] mbstring extension
    * [ ] pdo_mysql extension
    * [ ] pdo_sqlite extension
- [ ] [:simple-composer: Dependency manager](prerequis.md#dependency-manager)
- [ ] [:simple-curl: CURL command](prerequis.md#curl-command) 
- [ ] [:simple-docker: Local environment](prerequis.md#local-environment) 

___

## :simple-homebrew: Package manager

A developer's workstation (or that of anyone working in IT) must be well mastered. (This is also called a "setup".)

To make installing and maintaining your setup easier, we recommend using a "package manager"

A **"package manager"** (system-wide) installs the programs you know and love from the command line (CLI) with minimal friction. It:

- Eliminates permission pop-ups
- Hides GUI wizard-style installations
- Avoids the PATH pollution caused by installing many programs
- Avoids unexpected side effects when installing and uninstalling programs
- Automatically finds and installs dependencies
- Performs all the extra configuration steps needed to get a working program

**Installation**

=== ":fontawesome-brands-windows: WINDOWS via Scoop"

    !!! warning "Important information"
    
        Do not open PowerShell in administrator mode

    To install "Scoop", follow these instructions:

    Open a PowerShell terminal and type the following commands:
    ```bash
    Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
    ```
    ```bash
    Invoke-RestMethod -Uri https://get.scoop.sh | Invoke-Expression
    ```

    For more information, visit [https://scoop.sh](https://scoop.sh){:target="_blank"}

=== ":fontawesome-brands-windows: WINDOWS via Chocolatey"

    Follow the instructions on :simple-chocolatey: [https://community.chocolatey.org](https://community.chocolatey.org/){:target="_blank"}

=== ":fontawesome-brands-apple: MAC OS"

    !!! warning "Important information"
    
        You may need to install Apple's Xcode Command Line Tools.

        Type the command `xcode-select --install` in a terminal

    To install "Brew", follow the instructions in "Install Homebrew":
    
    ```bash
    /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
    ```

    For more information, visit [https://brew.sh](https://brew.sh){:target="_blank"}

___

## :simple-git: Version manager

We are going to use a distributed version control system called "GIT"

It lets you track the changes made to a set of files over time, which makes collaboration between developers easier.

It also offers advanced features such as the ability to go back to earlier versions of files, to resolve merge conflicts and to collaborate with other developers.

To find out whether GIT is installed on your workstation, type this command in a terminal

````Bash
git --version
````

!!! note "Expected result"

    This result is an example and will probably not be the same as yours.

    ````Bash
    git version 2.39.2 (Apple Git-143)
    ````

**Installation**

=== ":fontawesome-brands-windows: WINDOWS via Scoop"

     To install GIT, open a terminal (PowerShell) to install the bucket:

    ```bash
    scoop bucket add main
    ```
    ```bash
    scoop install main/git
    ```

    For more information, visit [https://scoop.sh/#/apps?q=git](https://scoop.sh/#/apps?q=git&id=95a0a6fa98a17215837dbe3e38b75262d6d4d7ae){:target="_blank"}

=== ":fontawesome-brands-windows: WINDOWS via Chocolatey"

     To install GIT, open a terminal (PowerShell) to install the bucket:

    ```bash
    choco install git
    ```

    For more information, visit [https://community.chocolatey.org/packages/git](https://community.chocolatey.org/packages/git){:target="_blank"}

=== ":fontawesome-brands-apple: MAC OS"

    To install GIT, open a terminal
    
    ```bash
    brew install git
    ```

    For more information, visit [https://formulae.brew.sh/formula/git](https://formulae.brew.sh/formula/git#default){:target="_blank"}

___

## :simple-php: Development environment

This framework uses the PHP language, more information at [https://www.php.net](https://www.php.net){:target="_blank"}

To find out whether PHP is installed on your machine, open a terminal and enter this command:

````Bash
php -v
````

!!! note "Expected result"

    This result is an example and will probably not be the same as yours.

    ````Bash
    PHP 8.4.11 (cli) (built: Sep  2 2026 16:08:36) (NTS)
    Copyright (c) The PHP Group
    Zend Engine v4.4.11, Copyright (c) Zend Technologies
    with Zend OPcache v8.4.11, Copyright (c), by Zend Technologies
    ````

**Installation**

!!! warning "Required version"

    The framework requires at least PHP 8.4; we recommend installing version 8.4 or later (8.5)

=== ":fontawesome-brands-windows: WINDOWS via Scoop"

     To install PHP, open a terminal (PowerShell) to install the bucket:

    ```bash
    scoop bucket add versions
    ```
    ```bash
    scoop install versions/php84
    ```

    For more information, visit [https://scoop.sh/#/apps?q=php](https://scoop.sh/#/apps?q=php&id=5101819badef2a2c45455bdb63c0036655741250){:target="_blank"}

=== ":fontawesome-brands-windows: WINDOWS via Chocolatey"

     To install PHP, open a terminal (PowerShell) to install the bucket:

    ```bash
    choco install php
    ```

    For more information, visit [https://community.chocolatey.org/packages/php](https://community.chocolatey.org/packages/php){:target="_blank"}

=== ":fontawesome-brands-apple: MAC OS"

    To install PHP, open a terminal
    
    ```bash
    brew install php@8.4
    ```

    For more information, visit [https://formulae.brew.sh/formula/php](https://formulae.brew.sh/formula/php#default){:target="_blank"}

### Enabling extensions

#### Where is your php.ini file
To find out where your php.ini file is located, you can use the following command:

````Bash
php --ini
````
You will get a result similar to this one:

````Bash
Configuration File (php.ini) Path: /usr/local/etc/php/8.4
Loaded Configuration File:         /usr/local/etc/php/8.4/php.ini
Scan for additional .ini files in: /usr/local/etc/php/8.4/conf.d 
Additional .ini files parsed:      /usr/local/etc/php/8.4/conf.d/ext-opcache.ini
````
In this example, the php.ini file is located in the "/usr/local/etc/php/8.4" folder. 

#### OpenSSL extension
To do this, open your php.ini file and uncomment the following line:

````text title="php.ini"
;extension=openssl
````
Remove the semicolon to enable the extension.

#### mbstring extension
To do this, open your php.ini file and uncomment the following line:

````Bash
;extension=mbstring
````
Remove the semicolon to enable the extension.

#### pdo_mysql extension
To do this, open your php.ini file and uncomment the following line:

````Bash
;extension=pdo_mysql
````
Remove the semicolon to enable the extension.

#### pdo_sqlite extension

!!! warning "From version v2.3.0 onwards"

    The `pdo_sqlite` extension is required for the logging service,
    the [debug bar and the profiler](../build/debug-bar.md) to work.

To do this, open your php.ini file and uncomment the following line:

````Bash
;extension=pdo_sqlite
````
Remove the semicolon to enable the extension.

#### excimer extension (optional)

The `excimer` extension enables the debug bar's [code profiler](../build/debug-bar.md#the-code-profiler).
It is already installed in the framework's Docker image. Outside Docker:

````Bash
pecl install excimer
````
Then add the line `extension=excimer` to your php.ini file.

___

## :simple-composer: Dependency manager

To manage the installation of libraries or packages in the project, we use a dependency manager called "COMPOSER".

To find out whether COMPOSER is installed on your machine, open a terminal and enter this command:

```Bash
composer --version
```

!!! note "Expected result"

    This result is an example and will probably not be the same as yours.

    ````Bash
    Composer version 2.5.8 
    ````

**Installation**

=== ":fontawesome-brands-windows: WINDOWS via Scoop"

     To install COMPOSER, open a terminal (PowerShell) to install the bucket:

    ```bash
    scoop bucket add main
    ```
    ```bash
    scoop install main/composer
    ```

    For more information, visit [https://scoop.sh/#/apps?q=composer](https://scoop.sh/#/apps?q=composer&id=9ce50f006acbb8649de41c1a38794e190a8c4453){:target="_blank"}

=== ":fontawesome-brands-windows: WINDOWS via Chocolatey"

     To install composer, open a terminal (PowerShell) to install the bucket:

    ```bash
    choco install composer
    ```

    For more information, visit [https://community.chocolatey.org/packages/composer](https://community.chocolatey.org/packages/composer){:target="_blank"}

=== ":fontawesome-brands-apple: MAC OS"

    To install COMPOSER, open a terminal
    
    ```bash
    brew install composer
    ```

    For more information, visit [https://formulae.brew.sh/formula/composer](https://formulae.brew.sh/formula/composer#default){:target="_blank"}

___

## :simple-curl: CURL command

Often used for HTTP requests, CURL is a command-line tool for transferring data with URLs.

It is often used to test APIs and scripts or to download files.

More information at [https://curl.se](https://curl.se){:target="_blank"}


```Bash
curl --version
```

!!! note "Expected result"

    This result is an example and will probably not be the same as yours.

    ````Bash
    curl 7.79.1 (x86_64-apple-darwin21.1.0) libcurl/7.79.1 (SecureTransport) LibreSSL/2.8.3 zlib/1.2.11 nghttp2/1.45.1
    Release-Date: 2021-09-22
    Protocols: dict file ftp ftps gopher http https imap imaps ldap ldaps pop3 pop3s rtsp smb smbs smtp smtps telnet tftp
    Features: alt-svc AsynchDNS GSS-API HTTP2 HTTPS-proxy IPv6 Kerberos Largefile libz MultiSSL NTLM NTLM_WB SPNEGO SSL UnixSockets
    ````

**Installation**


=== ":fontawesome-brands-windows: WINDOWS via Scoop"

    To install the "curl" command, follow these instructions:

    ```bash
    scoop bucket add main
    ```
    ```bash
    scoop install main/curl
    ```

    For more information, visit [https://scoop.sh/#/apps?q=curl](https://scoop.sh/#/apps?q=curl&id=09e3750a1f0a703b6de0f8fa4ca1263ef5d59ad3){:target="_blank"}

=== ":fontawesome-brands-windows: WINDOWS via Chocolatey"

     To install CURL, open a terminal (PowerShell) to install the bucket:

    ```bash
    choco install curl
    ```

    For more information, visit [https://community.chocolatey.org/packages/curl](https://community.chocolatey.org/packages/curl){:target="_blank"}

=== ":fontawesome-brands-apple: MAC OS"

    To install the "curl" command, follow these instructions:
    
    ```bash
    brew install curl
    ```

    For more information, visit [https://formulae.brew.sh/formula/curl](https://formulae.brew.sh/formula/curl#default){:target="_blank"}

___

## :simple-docker: Local environment

To emulate the services used by the framework, such as MySQL, we strongly encourage you to use Docker.

Docker is a lightweight virtualisation platform that makes it easy to isolate and manage applications in containers. With Docker, you can create and run containers for the various services your framework needs, such as MySQL, without having to install them directly on your machine. This saves you time and simplifies the development process. So don't hesitate to use Docker to emulate your services!

More information on installing [Docker desktop](https://www.docker.com){:target="_blank"}

The framework comes with a `compose.yml` file that lets you quickly start the services your project needs.
For more information, we come back to this in the "Starting the services" section.

!!! info "Important information"

    Using DOCKER is not mandatory. Nevertheless, Docker is an essential tool for modern developers.

    You can run the services directly on your machine (mysql, wamp ...), but this can be more complex and take longer. It also freezes the versions of the installed services, which can cause compatibility issues with the versions used by the framework.
