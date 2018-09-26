# Zipper

[![Build Status](https://travis-ci.org/Chumper/Zipper.png)](https://travis-ci.org/Chumper/Zipper)

This is a simple Wrapper around the ZipArchive methods with some handy functions.

## Installation

1. Add this package to the list of required packages, inside `composer.json`
  * for Laravel 5: `"chumper/zipper": "1.0.x"`
  * ~~for Laravel 4: `"chumper/zipper": "0.5.x"`~~
2. Run `composer update`

3. Go to `app/config/app.php`

  * add to providers `'Chumper\Zipper\ZipperServiceProvider'`
  * add to aliases `'Zipper' => 'Chumper\Zipper\Zipper'`

You can now access Zipper with the `Zipper` alias.

## Simple example
```php
$files = glob('public/files/*');
Zipper::make('public/test.zip')->add($files)->close();
```
- by default the package will create the `test.zip` in the project route folder but in the example above we changed it to `project_route/public/`.

## Another example
```php
$zipper = new \Chumper\Zipper\Zipper;

$zipper->make('test.zip')->folder('test')->add('composer.json');
$zipper->zip('test.zip')->folder('test')->add('composer.json','test');

$zipper->remove('composer.lock');

$zipper->folder('mySuperPackage')->add(
    array(
        'vendor',
        'composer.json'
    ),
);

$zipper->getFileContent('mySuperPackage/composer.json');

$zipper->make('test.zip')->extractTo('',array('mySuperPackage/composer.json'),Zipper::WHITELIST);

$zipper->close();
```

Note: Please be aware that you need to call `->close()` at the end to write the zip file to disk.

You can easily chain most functions, except `getFileContent`, `getStatus`, `close` and `extractTo` which must come at the end of the chain.

The main reason I wrote this little package is the `extractTo` method since it allows you to be very flexible when extracting zips. So you can for example implement an update method which will just override the changed files.


# Functions

## make($pathToFile)

`Create` or `Open` a zip archive; if the file does not exists it will create a new one.
It will return the Zipper instance so you can chain easily.


## add($files/folder)

You can add and array of Files, or a Folder which all the files in that folder will then be added, so from the first example we could instead do something like `$files = 'public/files/';`.


## addString($filename, $content)

add a single file to the zip by specifying a name and content as strings.


## remove($file/s)

removes a single file or an array of files from the zip.


## folder($folder)

Specify a folder to 'add files to' or 'remove files from' from the zip, example

	Zipper::make('test.zip')->folder('test')->add('composer.json');
	Zipper::make('test.zip')->folder('test')->remove('composer.json');


## listFiles($regexFilter = null)

Lists all files within archive (if no filter pattern is provided). Use `$regexFilter` parameter to filter files. See [Pattern Syntax](http://php.net/manual/en/reference.pcre.pattern.syntax.php) for regular expression syntax 

> NB: `listFiles` ignores folder set with `folder` function


Example: Return all files/folders ending/not ending with '.log' pattern (case insensitive). This will return matches in sub folders and their sub folders also

```php
$logFiles = Zipper::make('test.zip')->listFiles('/\.log$/i'); 
$notLogFiles = Zipper::make('test.zip')->listFiles('/^(?!.*\.log).*$/i'); 
```


## home()

Resets the folder pointer.

## zip($fileName)

Uses the ZipRepository for file handling.


## getFileContent($filePath)

get the content of a file in the zip. This will return the content or false.


## getStatus()

get the opening status of the zip as integer.


## close()

closes the zip and writes all changes.


## extractTo($path)

Extracts the content of the zip archive to the specified location, for example

    Zipper::make('test.zip')->folder('test')->extractTo('foo');

This will go into the folder `test` in the zip file and extract the content of that folder only to the folder `foo`, this is equal to using the `Zipper::WHITELIST`.

This command is really nice to get just a part of the zip file, you can also pass a 2nd & 3rd param to specify a single or an array of files that will be

> NB: Php ZipArchive uses internally '/' as directory separator for files/folders in zip. So Windows users should not set 
> whitelist/blacklist patterns with '\' as it will not match anything

white listed

>**Zipper::WHITELIST**

```php
Zipper::make('test.zip')->extractTo('public', array('vendor'), Zipper::WHITELIST);
```

Which will extract the `test.zip` into the `public` folder but **only** files/folders starting with `vendor` prefix inside the zip will be extracted.

or black listed

>**Zipper::BLACKLIST**
Which will extract the `test.zip` into the `public` folder except files/folders starting with `vendor` prefix inside the zip will not be extracted.


```php
Zipper::make('test.zip')->extractTo('public', array('vendor'), Zipper::BLACKLIST);
```

>**Zipper::EXACT_MATCH**

```php
Zipper::make('test.zip')
    ->folder('vendor')
    ->extractTo('public', array('composer', 'bin/phpunit'), Zipper::WHITELIST | Zipper::EXACT_MATCH);
```

Which will extract the `test.zip` into the `public` folder but **only** files/folders **exact matching names**. So this will:
 * extract file or folder named `composer` in folder named `vendor` inside zip to `public` resulting `public/composer`
 * extract file or folder named `bin/phpunit` in `vendor/bin/phpunit` folder inside zip to `public` resulting `public/bin/phpunit`

> **NB:** extracting files/folder from zip without setting Zipper::EXACT_MATCH 
> When zip has similar structure as below and only `test.bat` is given as whitelist/blacklist argument then `extractTo` would extract all those files and folders as they all start with given string

```
test.zip
 |- test.bat
 |- test.bat.~
 |- test.bat.dir/
    |- fileInSubFolder.log
```

## extractMatchingRegex($path, $regex)

Extracts the content of the zip archive matching regular expression to the specified location. See [Pattern Syntax](http://php.net/manual/en/reference.pcre.pattern.syntax.php) for regular expression syntax.

Example: extract all files ending with `.php` from `src` folder and its sub folders.
```php
Zipper::make('test.zip')->folder->('src')->extractMatchingRegex($path, '/\.php$/i'); 
```

Example: extract all files **except** those ending with `test.php` from `src` folder and its sub folders.
```php
Zipper::make('test.zip')->folder->('src')->extractMatchingRegex($path, '/^(?!.*test\.php).*$/i'); 
```

# Development

Maybe it is a good idea to add other compression functions like rar, phar or bzip2 etc...
Everything is setup for that, if you want just fork and develop further.

If you need other functions or got errors, please leave an issue on github.
