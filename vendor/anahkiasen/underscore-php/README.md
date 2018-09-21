# Underscore.php

[![Build Status](http://img.shields.io/travis/Anahkiasen/underscore-php.svg?style=flat)](https://travis-ci.org/Anahkiasen/underscore-php)
[![Latest Stable Version](http://img.shields.io/packagist/v/anahkiasen/underscore-php.svg?style=flat)](https://packagist.org/packages/anahkiasen/underscore-php)
[![Total Downloads](http://img.shields.io/packagist/dt/anahkiasen/underscore-php.svg?style=flat)](https://packagist.org/packages/anahkiasen/underscore-php)
[![Scrutinizer Quality Score](http://img.shields.io/scrutinizer/g/Anahkiasen/underscore-php.svg?style=flat)](https://scrutinizer-ci.com/g/Anahkiasen/underscore-php/)
[![Code Coverage](http://img.shields.io/scrutinizer/coverage/g/Anahkiasen/underscore-php.svg?style=flat)](https://scrutinizer-ci.com/g/Anahkiasen/underscore-php/)
[![Support via Gittip](http://img.shields.io/gittip/Anahkiasen.svg?style=flat)](https://www.gittip.com/Anahkiasen/)

## The PHP manipulation toolbelt

First off : Underscore.php is **not** a PHP port of [Underscore.js][] (well ok I mean it was at first).
It's doesn't aim to blatantly port its methods, but more port its philosophy.

It's a full-on PHP manipulation toolbet sugar-coated by an elegant syntax directly inspired by the [Laravel framework][]. Out through the window went the infamous `__()`, replaced by methods and class names that are meant to be read like sentences _à la_ Rails : `Arrays::from($article)->sortBy('author')->toJSON()`.

It features a good hundred of methods for all kinds of types : strings, objects, arrays, functions, integers, etc., and provides a parsing class that help switching from one type to the other mid-course. Oh also it's growing all the time.
The cherry on top ? It wraps nicely around native PHP functions meaning `Strings::replace` is actually a dynamic call to `str_replace` but with the benefit of allowed chaining and a **finally** consistant argument order (**all** functions in _Underscore_ put the subject as the first argument, NO MATTER WHAT).

It works both as a stand-alone via *Composer* or as a bundle for the Laravel framework. So you know, you don't really have any excuse.

## Install Underscore

To install Underscore.php simply run `composer require anahkiasen/underscore-php`.
Note that Underscore's type classes (Arrays/Strings/etc) are by default namespaced in the `Types` folder, so to use Arrays, you would do the following :

```php
use Underscore\Types\Arrays;
```

## Using Underscore

It can be used both as a static class, and an Object-Oriented class, so both the following are valid :

```php
$array = array(1, 2, 3);

// One-off calls to helpers
Arrays::each($array, function($value) { return $value * $value; }) // Square the array
Function::once($myFunction) // Only allow the function to be called once
Number::paddingLeft(5, 5) // Returns '00005'
Object::methods($myObject) // Return the object's methods
Strings::length('foobar') // Returns 6

// Or chain calls with the 'from' method
Arrays::from($array)->filter(...)->sort(...)->get(2)

// Which does this in the background
$array = new Arrays($array);
$array->filter(...)->sort(...)->get(2)
```

For those nostalgic of ye old `__()` a generic `Underscore` class is provided that is able to go and fetch methods in all of Underscore.php's methods. For this it looks into the methods it knows and analyzes the subject of the method (meaning if you do `Underscore::contains('foobar', 'foo')` it knows you're not looking for `Arrays::contains`).

On types : it's important to note that using a specific type class to create an Underscore repository will convert the type of provided subject. Say you have an object and do `new Arrays($myObject)` – this will convert the object to an array and allow you to use Arrays methods on it.
For this Underscore uses its **Parse** class's methods that for the most part just type cast and return (like this `(array) $object`) but it also sometimes go the extra mile to understand what you want to do : if you convert an array to a string, it will transform it to JSON, it you transform an array into an integer, it returns the size of the array, etc.

The core concept is this : static calls return values from their methods, while chained calls update the value of the object they're working on. Which means that an Underscore object don't return its value until you call the `->obtain` method on it — until then you can chain as long as you want, it will remain an object.
The exception are certains properties that are considered _breakers_ and that will return the object's value. An example is `Arrays->get`.

Note that since all data passed to Underscore is transformed into an object, you can do this sort of things, plus the power of chained methods, it all makes the manipulation of data a breeze.

```php
$array = new Arrays(['foo' => 'bar']);

echo $array->foo // Returns 'bar'

$array->bis = 'ter'
$array->obtain() // Returns array('foo' => 'bar', 'bis' => 'ter')
```

### Customizing Underscore

Underscore.php provides the ability to extend any class with custom functions so go crazy.
Don't forget that if you think you have a function anybody could enjoy, do a pull request, let everyone enjoy it !

```php
Strings::extend('summary', function($string) {
  return Strings::limitWords($string, 10, '... — click to read more');
});

Strings::from($article->content)->summary()->title()
```

You can also give custom aliases to all of Underscore's methods, in the provided config file. Just add entries to the `aliases` option, the key being the alias, and the value being the method to point to.

### Extendability

Underscore.php's classes are extendable as well in an OOP sense. You can update an Underscore repository with the `setSubject` method (or directly via `$this->subject =` granted you return `$this` at the end).
When creating an Underscore repository, by default it's subject is an empty string, you can change that by returning whatever you want in the `getDefault` method.

```
class Users extends Arrays
{
  public function getDefault()
  {
    return 'foobar';
  }

  public function getUsers()
  {
    // Fetch data from anywhere

    return $this->setSubject($myArrayOfUsers);
  }
}

$users = new Users; // Users holds 'foobar'
$users->getUsers()->sort('name')->clean()->toCSV()

// Same as above
Users::create()->getUsers()->sort('name')->clean()->toCSV()
```

It is important to not panic about the mass of methods present in Underscore and the dangers extending one of the Types would cause : the methods aren't contained in the classes themselves but in methods repositories. So if you extend the `Strings` class and want to have a `length` method on your class that has a completely different meaning than `Strings::length`, it won't cause any signature conflict or anything.

Also note that Underscore method router is dynamic so if your subject is an array and mid course becomes a string, Underscore will always find the right class to call, no matter what you extended in the first place. Try to keep track of your subject though : if your subject becomes a string, calling per example `->map` will return an error.

### Call to native methods

Underscore natively extends PHP, so it can automatically reference original PHP functions when the context matches. Now, PHP by itself doesn't have a lot of conventions so `Arrays::` look for `array_` functions, `Strings::` look for `str_` plus a handful of other hardcoded redirect, but that's it.
The advantage is obviously that it allows chaining on a lot of otherwise one-off functions or that only work by reference.

```php
Arrays::diff($array, $array2, $array3) // Calls `array_diff`
Arrays::from($array)->diff($array2, $array3)->merge($array4) // Calls `array_diff` then `array_merge` on the result
```

## Documentation

You can find a detailed summary of all classes and methods in the [repo's wiki][] or the [official page][].
The changelog is available in the [CHANGELOG][] file.

## About Underscore.php

There is technically another port of Underscore.js to PHP available [on Github][] — I first discovered it when I saw it was for a time used on Laravel 4. I quickly grew disapoint of what a mess the code was, the lack of updates, and the 1:1 mentality that went behind it.

This revamped Underscore.php doesn't aim to be a direct port of Underscore.js. It sometimes omits methods that aren't relevant to PHP developers, rename others to match terms that are more common to them, provides a richer syntax, adds a whole lot of methods, and leaves room for future ones to be added all the time — whereas the previous port quickly recoded all JS methods to PHP and left it at that.

If you come from Javascript and are confused by some of the changes, don't put all the blame on me for trying to mess everything up. A basic example is the `map` function : in PHP it has a completely different sense because there exists an `array_map` function that basically does what `__::invoke` does in JS. So `map` is now `Arrays::each`.
Always keep in mind this was made for _PHP_ developpers first, and differences **do** exist between the two to accomdate the common terms in PHP.

[CHANGELOG]: https://github.com/Anahkiasen/underscore-php/blob/master/CHANGELOG.md
[official page]: http://anahkiasen.github.com/underscore-php/
[Laravel framework]: http://laravel.com/
[Underscore.js]: https://github.com/documentcloud/underscore
[repo's wiki]: https://github.com/Anahkiasen/underscore-php/wiki/_pages
[on Github]: https://github.com/brianhaveri/Underscore.php
