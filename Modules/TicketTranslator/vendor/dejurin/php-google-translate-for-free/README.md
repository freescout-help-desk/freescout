# GoogleTranslateForFree
Library for free use Google Translator. With attempts connecting on failure and array support.

## Installation

Install this package via [Composer](https://getcomposer.org/).

```
composer require dejurin/php-google-translate-for-free
```

Or edit your project's `composer.json` to require `dejurin/php-google-translate-for-free` and then run `composer update`.

```json
"require": {
    "dejurin/php-google-translate-for-free": "^1.0"
}
```

## Usage

```php
require_once ('vendor/autoload.php');
use \Dejurin\GoogleTranslateForFree;
```

## Single

```php
$source = 'en';
$target = 'ru';
$attempts = 5;
$text = 'Hello';

$tr = new GoogleTranslateForFree();
$result = $tr->translate($source, $target, $text, $attempts);

echo $result; 

/* 
	string(24) "Здравствуйте" 
*/
```

## Array

```php
$source = 'en';
$target = 'ru';
$attempts = 5;
$arr = array('hello','world');

$tr = new GoogleTranslateForFree();
$result = $tr->translate($source, $target, $arr, $attempts);

echo $result;

/*
	array(2) {
	  [0]=>
	  string(24) "Здравствуйте"
	  [1]=>
	  string(6) "Мир"
	}

*/
```