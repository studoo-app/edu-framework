# Request: Handling HTTP requests

Request is a class used to retrieve the data of the HTTP request. 

It lets you retrieve the request parameters (POST and GET), the route, the headers, etc.

## Usage

To use the Request class, simply instantiate it in the controller.

```php
$request = new Request("/hello", "GET");
```

Request takes two parameters:

- The route of the request
- The method of the request

## Methods

Request has several methods to retrieve the data of the request.

### getHearder()
Retrieves the headers of the HTTP request

```php
$request->getHeader();
```
It returns an associative array of the request headers.

Example:

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
This method returns an associative array of the HTTP parameters, combining all the GET and POST parameters.

```php
$request->getVars();
```

Example:

```php
array(2) {
  ["prenom"]=>
  string(6) "benoit"
  ["nom"]=>
  string(3) "Hay"
}
```

### get($key)
This method uses its argument to look up a GET or POST parameter in the HTTP request

HTTP GET example: 

Here is an HTTP GET request http://studoo.app/inscription?id=23&token=dzefzerfgtrYrEZaDADFRHhrtgfEferFerfe

In this example, the HTTP GET request has two parameters in key=value format:

| Key   | Value |
|-------|--------|
| id    | 23     |
| token | dzefzerfgtrYrEZaDADFRHhrtgfEferFerfe |

The & operator separates two parameters.

!!! warning "Warning"
    
    Be careful not to use special characters or spaces

```php
$idUser = $request->get("id");
echo $idUser;
```
In this example, we retrieve "id" from the HTTP GET request (through its URL) 

Result:
```
23
```

HTTP POST example:

Here is an HTTP POST request http://studoo.app/inscription

```html
<form action="/inscription" method="post">
    <input type="text" name="id" value="23">
    <input type="text" name="token" value="dzefzerfgtrYrEZaDADFRHhrtgfEferFerfe">
    <input type="submit" value="Send">
</form>
```

In this example, the HTTP POST request has two parameters in key=value format:

| Key   | Value |
|-------|--------|
| id    | 23     |
| token | dzefzerfgtrYrEZaDADFRHhrtgfEferFerfe |

```php
$idUser = $request->get("id");
echo $idUser;
``` 
In this example, we retrieve "id" from the HTTP POST request 

Result:
```
23
```

### getHttpMethod()
This method returns the HTTP method used by the request (POST, GET)

HTTP POST example:
Here is an HTTP POST request http://studoo.app/inscription

```html
<form action="/inscription" method="post">
    <input type="text" name="id" value="23">
    <input type="text" name="token" value="dzefzerfgtrYrEZaDADFRHhrtgfEferFerfe">
    <input type="submit" value="Send">
</form>
```

In this example, the HTTP POST request:

```php
if ($request->getHttpMethod() === "POST") {
    echo "This is a POST request";
}
```
Result:
```
This is a POST request
```

### getRoute()
This method returns the route associated with the HTTP request

HTTP POST example:

Here is an HTTP POST request http://studoo.app/inscription

```html
<form action="/inscription" method="post">
    <input type="text" name="id" value="23">
    <input type="text" name="token" value="dzefzerfgtrYrEZaDADFRHhrtgfEferFerfe">
    <input type="submit" value="Send">
</form>
```

In this example, the HTTP POST request:

```php
echo $request->getRoute();
```
Result:
```
/inscription
```

## File handling (upload)

Request lets you handle files sent through an HTML form with the `enctype="multipart/form-data"` attribute.

!!! warning "Form required"

    To send files, the HTML form **must** have the `enctype="multipart/form-data"` attribute:

    ```html
    <form action="/medecin/import" method="post" enctype="multipart/form-data">
        <input type="file" name="fichier">
        <input type="submit" value="Import">
    </form>
    ```

### hasFile()
Tells you whether the form sent a file for this field.

```php
if ($request->hasFile('fichier')) {
    // A file was sent for the "fichier" field
}
```

!!! warning "Field left empty"

    A file field left empty still creates an entry in `$_FILES` with `error = 4` (`UPLOAD_ERR_NO_FILE`).
    `hasFile()` returns `true` but `isValid()` returns `false`.

### getFile()
Returns the **list** of uploaded files for a form field, or `null` if the field does not exist.

```php
$fichiers = $request->getFile('fichier');
```

Result:

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

#### Why a list? Normalizing $_FILES

In native PHP, the structure of `$_FILES` changes shape completely depending on the HTML field:

| HTML field | Native structure of `$_FILES` |
|---|---|
| `<input type="file" name="fichier">` | `$_FILES['fichier']['name']` is a **string** |
| `<input type="file" name="fichiers[]">` | `$_FILES['fichiers']['name']` is an **array** (as are `type`, `size`, `tmp_name`, `error`) |

The framework **normalizes** this structure when it is injected: each field always points to a **list** of files, with the same keys (`name`, `type`, `size`, `tmp_name`, `error`). You no longer have to handle both shapes.

### getFiles()
Returns all the uploaded files of the request (normalized structure: each field → a list of files).

```php
$tousLesFichiers = $request->getFiles();
```

### isValid()
Checks that the file(s) of the field are valid, meaning the upload completed without error (`error = 0`, constant `UPLOAD_ERR_OK`).

```php
// Checks all the files of the field
$request->isValid('fichier');

// Checks only the file at index 1 (multi-file field)
$request->isValid('fichiers', 1);
```

### getExtension()
Returns the extension of the uploaded file (in lowercase), or `null` if the file is missing or invalid.

```php
$extension = $request->getExtension('fichier'); // "csv"
```

### move()
Moves the uploaded file from its temporary location (`tmp_name`) to its final destination.
This method wraps the PHP function `move_uploaded_file()`, checking beforehand that the upload is valid. It returns `false` if something goes wrong.

```php
$destination = __DIR__ . '/../../public/upload/mon-fichier.csv';
if ($request->move('fichier', $destination) === true) {
    // The file has been moved
}
```

## Complete example

A complete, working example is available in the framework:
the [/medecin/import](http://localhost:8042/medecin/import) route lets you upload a CSV or TXT file.

```php
public function import(Request $request): string|null
{
    $message = null;
    $erreur  = null;

    if ($request->getHttpMethod() === "POST") {
        if ($request->hasFile('fichier') === false) {
            $erreur = "No file was sent";
        } elseif ($request->isValid('fichier') === false) {
            $erreur = "The file is not valid (size too large or upload interrupted)";
        } elseif (in_array($request->getExtension('fichier'), ['csv', 'txt'], true) === false) {
            $erreur = "Extension not allowed";
        } else {
            $destination = $dossierUpload . '/import-' . uniqid() . '.' . $request->getExtension('fichier');
            if ($request->move('fichier', $destination) === true) {
                $message = "The file was uploaded successfully";
            } else {
                $erreur = "Problem while saving the file";
            }
        }
    }

    return TwigCore::getEnvironment()->render('medecin/import.html.twig', [...]);
}
```

!!! warning "Security"

    - Always check `isValid()` **and** the extension on the server side with an **allowlist** (never a blocklist)
    - **Never** trust the file name provided by the client: generate the final name on the server side (Example: `uniqid()`)
    - Always use `move_uploaded_file()` (via `move()`) and not `copy()`: it is the only safe way to handle an uploaded file
