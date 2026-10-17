# Contributing Documentation

Pour ce projet, nous utilisons ProperDocs, le fork maintenu de MkDocs, pour la documentation. Voici quelques informations pour vous aider à contribuer à la documentation.

!!! abstract "Prérequis"

    Nous vous recommandons d'installer l'environnement virtuel Python pour installer ProperDocs.

    Python 3.6 ou supérieur est requis pour l'installation de ProperDocs.


## Installation de l'environnement Python

=== ":fontawesome-brands-windows: WINDOWS via Scoop"

     Pour installation Python, ouvrez un terminal (PowerShell) pour installer le bucket :

    ```bash
    scoop bucket add main
    ```
    ```bash
    scoop install main/python
    ```

    Pour plus informations, visitez le site [https://scoop.sh/#/apps?q=python](https://scoop.sh/#/apps?q=python&id=2dcee5c280bcf909518d9074ed86f67c984f4db0){:target="_blank"}

=== ":fontawesome-brands-windows: WINDOWS via Chocolatey"

     Pour installation Python, ouvrez un terminal (PowerShell) pour installer le bucket :

    ```bash
    choco install python
    ```

    Pour plus informations, visitez le site [https://community.chocolatey.org/packages/python](https://community.chocolatey.org/packages/python){:target="_blank"}

=== ":fontawesome-brands-apple: MAC OS"

    Pour installation Python, ouvrez un terminal
    
    ```bash
    brew install python@3.12
    ```

    Pour plus informations, visitez le site [https://formulae.brew.sh/formula/python](https://formulae.brew.sh/formula/python@3.12){:target="_blank"}

    La génération des cartes sociales (plugin `material/social`) utilise `cairosvg`, qui nécessite la bibliothèque native `cairo` :

    ```bash
    brew install cairo
    ```


## Installation de l'environnement virtuel Python   

Créer un environnement virtuel Python avec la commande suivante :

```bash
python3 -m venv venv
```

!!! warning "macOS : vérifiez votre Python"

    Si `python3` correspond au Python fourni par Apple (`/usr/bin/python3` ou `/Library/Developer/CommandLineTools/...`), le venv ne trouvera pas les bibliothèques installées via Homebrew et `properdocs serve` échouera avec l'erreur `no library called "cairo-2" was found`.

    Vérifiez avec `command -v python3` que vous utilisez bien le Python de Homebrew (`/opt/homebrew/bin/python3`) avant de créer le venv.

!!! abstract "ENVironnement Virtuel"

    Pourquoi utiliser un environnement virtuel Python ? [https://docs.python.org/3/library/venv.html(https://docs.python.org/3/library/venv.html){:target="_blank"}


## Activation de l'environnement virtuel

Pour activer l'environnement virtuel Python, ouvrez un terminal et tapez la commande suivante :

=== ":fontawesome-brands-windows: WINDOWS via PowerShell"

     Ouvrez un terminal (PowerShell) :

    ```bash
    _venv_\Scripts\Activate.ps1
    ```

=== ":fontawesome-brands-windows: WINDOWS via CMD"

    Ouvrez un terminal (CMD) :

    ```bash
    venv\Scripts\activate.bat
    ```

=== ":fontawesome-brands-apple: MAC OS"

    Ouvrez un terminal

    ```bash
    source venv/bin/activate
    ```

## Installation de ProperDocs

Pour installer ProperDocs, tapez la commande suivante :
Cette commande va installer ProperDocs et les dépendances nécessaires.

```bash
python -m pip install -r requirements.txt
```
!!! abstract "Pour aller plus loin"

    Nous utilisons le thème "Material" pour la documentation. 

    Pour plus d'informations, visitez le site [https://squidfunk.github.io/mkdocs-material/](https://squidfunk.github.io/mkdocs-material/){:target="_blank"}

    ProperDocs est le fork maintenu de MkDocs, le projet original n'étant plus maintenu depuis août 2024.

    Pour plus d'informations, visitez le site [https://properdocs.org/](https://properdocs.org/){:target="_blank"}

## Lancement de ProperDocs

```bash
properdocs serve
```

ProperDocs va lancer un serveur local pour visualiser la documentation. 

Ouvrez un navigateur et tapez l'adresse [https://127.0.0.1:8000/edu-framework/](https://127.0.0.1:8000/edu-framework/){:target="_blank"}

!!! abstract "Une fois installé"

    Une fois installé, vous pouvez contribuer à la documentation en modifiant les fichiers Markdown dans le dossier "docs" du projet.
    N'oubliez pas de travailler sur la branche "main" pour vos modifications et de faire une pull request pour les valider.
    
    Si vous avez fermé le terminal, n'oubliez pas d'activer l'environnement virtuel Python avant de lancer ProperDocs.
    