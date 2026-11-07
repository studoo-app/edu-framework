# Contributing Documentation

For this project, we use ProperDocs, the maintained fork of MkDocs, for the documentation. Here is some information to help you contribute to the documentation.

!!! abstract "Prerequisites"

    We recommend installing a Python virtual environment to install ProperDocs.

    Python 3.6 or higher is required to install ProperDocs.


## Installing the Python environment

=== ":fontawesome-brands-windows: WINDOWS via Scoop"

     To install Python, open a terminal (PowerShell) to install the bucket:

    ```bash
    scoop bucket add main
    ```
    ```bash
    scoop install main/python
    ```

    For more information, visit [https://scoop.sh/#/apps?q=python](https://scoop.sh/#/apps?q=python&id=2dcee5c280bcf909518d9074ed86f67c984f4db0){:target="_blank"}

=== ":fontawesome-brands-windows: WINDOWS via Chocolatey"

     To install Python, open a terminal (PowerShell) to install the package:

    ```bash
    choco install python
    ```

    For more information, visit [https://community.chocolatey.org/packages/python](https://community.chocolatey.org/packages/python){:target="_blank"}

=== ":fontawesome-brands-apple: MAC OS"

    To install Python, open a terminal
    
    ```bash
    brew install python@3.12
    ```

    For more information, visit [https://formulae.brew.sh/formula/python](https://formulae.brew.sh/formula/python@3.12){:target="_blank"}

    Generating social cards (the `material/social` plugin) uses `cairosvg`, which requires the native `cairo` library:

    ```bash
    brew install cairo
    ```


## Installing the Python virtual environment   

Create a Python virtual environment with the following command:

```bash
python3 -m venv venv
```

!!! warning "macOS: check your Python"

    If `python3` is the Python shipped by Apple (`/usr/bin/python3` or `/Library/Developer/CommandLineTools/...`), the venv will not find the libraries installed with Homebrew and `properdocs serve` will fail with the error `no library called "cairo-2" was found`.

    Check with `command -v python3` that you are using Homebrew's Python (`/opt/homebrew/bin/python3`) before creating the venv.

!!! abstract "Virtual ENVironment"

    Why use a Python virtual environment? [https://docs.python.org/3/library/venv.html](https://docs.python.org/3/library/venv.html){:target="_blank"}


## Activating the virtual environment

To activate the Python virtual environment, open a terminal and type the following command:

=== ":fontawesome-brands-windows: WINDOWS via PowerShell"

     Open a terminal (PowerShell):

    ```bash
    _venv_\Scripts\Activate.ps1
    ```

=== ":fontawesome-brands-windows: WINDOWS via CMD"

    Open a terminal (CMD):

    ```bash
    venv\Scripts\activate.bat
    ```

=== ":fontawesome-brands-apple: MAC OS"

    Open a terminal

    ```bash
    source venv/bin/activate
    ```

## Installing ProperDocs

To install ProperDocs, type the following command.
This command installs ProperDocs and the required dependencies.

```bash
python -m pip install -r requirements.txt
```
!!! abstract "Going further"

    We use the "Material" theme for the documentation. 

    For more information, visit [https://squidfunk.github.io/mkdocs-material/](https://squidfunk.github.io/mkdocs-material/){:target="_blank"}

    ProperDocs is the maintained fork of MkDocs, as the original project has no longer been maintained since August 2024.

    For more information, visit [https://properdocs.org/](https://properdocs.org/){:target="_blank"}

## Running ProperDocs

```bash
properdocs serve
```

ProperDocs starts a local server so you can preview the documentation. 

Open a browser and go to [https://127.0.0.1:8000/edu-framework/](https://127.0.0.1:8000/edu-framework/){:target="_blank"}

## A bilingual documentation

The documentation is available in French (default language) and in English.

- Each page `page.md` (in French) can have an English counterpart `page.en.md` in the same folder.
- The English navigation is defined in the `docs/_summary.en.md` file (the French navigation is in `docs/_summary.md`).
- A page without a `.en.md` file is displayed in French in the English version of the site.
- In links between pages, always point to the French file name (`page.md`): the English version is resolved automatically.

!!! tip "Keep both versions in sync"

    When you edit a page, please update both versions (`page.md` and `page.en.md`) whenever possible.
    If you cannot translate your changes, mention it in your pull request.

!!! abstract "Once installed"

    Once installed, you can contribute to the documentation by editing the Markdown files in the project's "docs" folder.
    Remember to work on the "main" branch for your changes and to open a pull request to have them validated.
    
    If you closed the terminal, remember to activate the Python virtual environment before running ProperDocs.