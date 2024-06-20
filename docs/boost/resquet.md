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
