# Request : La gestion des requêtes HTTP

Request est une classe qui permet de récupérer les données de la requête HTTP. 

Elle permet de récupérer les paramètres de la requête (POST et GET), la route, les headers, etc.

## Utilisation

Pour utiliser la classe Request, il suffit de l'instancier dans le controller.

```php
$request = new Request("/hello", "GET");
```

Request a deux paramètres :

- La route de la requête
- La méthode de la requête

## Méthodes

Request a plusieurs méthodes pour récupérer les données de la requête.

### getHearder()
Permet de récupérer les headers de la requête HTTP

```php
$request->getHeader();
```
Elle retourne un tableau associatif des headers de la requête.

Exemple :

```php
array() {
  ["Host"]=>
  string(14) "localhost:8042"
  ["Connection"]=>
  string(10) "keep-alive"
  ["Cache-Control"]=>
  string(9) "max-age=0"
  ["sec-ch-ua-platform"]=>
  string(7) ""macOS""
  ["User-Agent"]=>
  string(117) "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36"
  ["Accept"]=>
  string(135) "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7"
  ["Accept-Encoding"]=>
  string(23) "gzip, deflate, br, zstd"
  ["Accept-Language"]=>
  string(2) "fr"
  ["Cookie"]=>
  string(263) "PHPSESSID=64vuohnregis3vm1885lb6123s; pma_lang=fr;"
}
```

### getVars()
Cette méthode renvoie un tableau associatif des paramètres HTTP et regroupe l'ensemble des paramètres GET et POST.

```php
$request->getVars();
```

Exemple :

```php
array(2) {
  ["prenom"]=>
  string(6) "benoit"
  ["nom"]=>
  string(3) "Hay"
}
```

### get($key)
Cette méthode permet de cherche grace à son argument un paramètre GET, POST dans la requête HTTP

Exemple HTTP GET : 

Voici une requête HTTP GET http://studoo.app/inscription?id=23&token=dzefzerfgtrYrEZaDADFRHhrtgfEferFerfe

Dans cet exemple, la requête HTTP de type GET a deux paramètres aux formats clé=valeur :

| Clé   | Valeur |
|-------|--------|
| id    | 23     |
| token | dzefzerfgtrYrEZaDADFRHhrtgfEferFerfe |

L'opérateur & représente la séparation entre deux paramètres.

!!! warning "Attention"
    
    Attention de ne pas mettre des caractères spéciaux et d'espace

```php
$idUser = $request->get("id");
echo $idUser;
```
Dans cet exemple, on recupere "id" de la requete HTTP de type GET (via son URL) 

Résultat :
```
23
```

Exemple HTTP POST :

Voici une requête HTTP POST http://studoo.app/inscription

```html
<form action="/inscription" method="post">
    <input type="text" name="id" value="23">
    <input type="text" name="token" value="dzefzerfgtrYrEZaDADFRHhrtgfEferFerfe">
    <input type="submit" value="Envoyer">
</form>
```

Dans cet exemple, la requête HTTP de type POST a deux paramètres aux formats clé=valeur :

| Clé   | Valeur |
|-------|--------|
| id    | 23     |
| token | dzefzerfgtrYrEZaDADFRHhrtgfEferFerfe |

```php
$idUser = $request->get("id");
echo $idUser;
``` 
Dans cet exemple, on recupere "id" de la requete HTTP de type POST 

Résultat :
```
23
```

### getHttpMethod()
Cette méthode renvoi la méthode HTTP utilisé par la requête (POST, GET)

Exemple HTTP POST :
Voici une requête HTTP POST http://studoo.app/inscription

```html
<form action="/inscription" method="post">
    <input type="text" name="id" value="23">
    <input type="text" name="token" value="dzefzerfgtrYrEZaDADFRHhrtgfEferFerfe">
    <input type="submit" value="Envoyer">
</form>
```

Dans cet exemple, la requête HTTP de type POST :

```php
if ($request->getHttpMethod() === "POST") {
    echo "C'est une requête POST";
}
```
Résultat :
```
C'est une requête POST
```

### getRoute()
Cette méthode renvoi la route associé à la requête HTTP

Exemple HTTP POST :

Voici une requête HTTP POST http://studoo.app/inscription

```html
<form action="/inscription" method="post">
    <input type="text" name="id" value="23">
    <input type="text" name="token" value="dzefzerfgtrYrEZaDADFRHhrtgfEferFerfe">
    <input type="submit" value="Envoyer">
</form>
```

Dans cet exemple, la requête HTTP de type POST :

```php
echo $request->getRoute();
```
Résultat :
```
/inscription
```

## Gestion des fichiers (upload)

Request permet de gérer les fichiers envoyés via un formulaire HTML avec l'attribut `enctype="multipart/form-data"`.

!!! warning "Formulaire obligatoire"

    Pour envoyer des fichiers, le formulaire HTML **doit** avoir l'attribut `enctype="multipart/form-data"` :

    ```html
    <form action="/medecin/import" method="post" enctype="multipart/form-data">
        <input type="file" name="fichier">
        <input type="submit" value="Importer">
    </form>
    ```

### hasFile()
Permet de savoir si le formulaire a envoyé un fichier pour ce champ.

```php
if ($request->hasFile('fichier')) {
    // Un fichier a été envoyé pour le champ "fichier"
}
```

!!! warning "Champ laissé vide"

    Un champ file laissé vide génère quand même une entrée dans `$_FILES` avec `error = 4` (`UPLOAD_ERR_NO_FILE`).
    `hasFile()` retourne `true` mais `isValid()` retourne `false`.

### getFile()
Renvoie la **liste** des fichiers téléversés pour un champ du formulaire, ou `null` si le champ n'existe pas.

```php
$fichiers = $request->getFile('fichier');
```

Résultat :

```php
array(1) {
  [0]=>
  array(5) {
    ["name"]=>     string(12) "medecins.csv"
    ["type"]=>     string(9)  "text/csv"
    ["size"]=>     int(123)
    ["tmp_name"]=> string(14) "/tmp/phpXYZ"
    ["error"]=>    int(0)
  }
}
```

#### Pourquoi une liste ? La normalisation de $_FILES

En PHP natif, la structure de `$_FILES` change complètement de forme selon le champ HTML :

| Champ HTML | Structure native de `$_FILES` |
|---|---|
| `<input type="file" name="fichier">` | `$_FILES['fichier']['name']` est une **chaine** |
| `<input type="file" name="fichiers[]">` | `$_FILES['fichiers']['name']` est un **tableau** (`type`, `size`, `tmp_name`, `error` aussi) |

Le framework **normalise** cette structure à l'injection : chaque champ pointe toujours vers une **liste** de fichiers, avec les mêmes clés (`name`, `type`, `size`, `tmp_name`, `error`). Vous n'avez plus à gérer les deux formes.

### getFiles()
Renvoie l'ensemble des fichiers téléversés de la requête (structure normalisée : chaque champ → une liste de fichiers).

```php
$tousLesFichiers = $request->getFiles();
```

### isValid()
Vérifie que le(s) fichier(s) du champ sont valides, c'est-à-dire que le téléversement s'est terminé sans erreur (`error = 0`, constante `UPLOAD_ERR_OK`).

```php
// Vérifie tous les fichiers du champ
$request->isValid('fichier');

// Vérifie uniquement le fichier à l'index 1 (champ multi-fichiers)
$request->isValid('fichiers', 1);
```

### getExtension()
Renvoie l'extension du fichier téléversé (en minuscules), ou `null` si le fichier est absent ou invalide.

```php
$extension = $request->getExtension('fichier'); // "csv"
```

### move()
Déplace le fichier téléversé depuis son emplacement temporaire (`tmp_name`) vers sa destination finale.
Cette méthode encapsule la fonction PHP `move_uploaded_file()` en vérifiant au préalable que le téléversement est valide. Elle retourne `false` en cas de problème.

```php
$destination = __DIR__ . '/../../public/upload/mon-fichier.csv';
if ($request->move('fichier', $destination) === true) {
    // Le fichier a été déplacé
}
```

## Exemple complet

Un exemple complet et fonctionnel est disponible dans le framework :
la route [/medecin/import](http://localhost:8042/medecin/import) permet de téléverser un fichier CSV ou TXT.

```php
public function import(Request $request): string|null
{
    $message = null;
    $erreur  = null;

    if ($request->getHttpMethod() === "POST") {
        if ($request->hasFile('fichier') === false) {
            $erreur = "Aucun fichier n'a été envoyé";
        } elseif ($request->isValid('fichier') === false) {
            $erreur = "Le fichier n'est pas valide (taille trop grande ou téléversement interrompu)";
        } elseif (in_array($request->getExtension('fichier'), ['csv', 'txt'], true) === false) {
            $erreur = "Extension non autorisée";
        } else {
            $destination = $dossierUpload . '/import-' . uniqid() . '.' . $request->getExtension('fichier');
            if ($request->move('fichier', $destination) === true) {
                $message = "Le fichier a bien été téléversé";
            } else {
                $erreur = "Problème lors de l'enregistrement du fichier";
            }
        }
    }

    return TwigCore::getEnvironment()->render('medecin/import.html.twig', [...]);
}
```

!!! warning "Sécurité"

    - Vérifiez toujours `isValid()` **et** l'extension côté serveur avec une **liste blanche** (jamais une liste noire)
    - Ne faites **jamais** confiance au nom de fichier fourni par le client : générez le nom final côté serveur (Exemple: `uniqid()`)
    - Utilisez toujours `move_uploaded_file()` (via `move()`) et non `copy()` : c'est la seule façon sûre de manipuler un fichier téléversé
