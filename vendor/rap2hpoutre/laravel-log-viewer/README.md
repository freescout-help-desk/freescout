Laravel 5 log viewer
======================

[![Packagist](https://img.shields.io/packagist/v/rap2hpoutre/laravel-log-viewer.svg)](https://packagist.org/packages/rap2hpoutre/laravel-log-viewer)
[![Packagist](https://img.shields.io/packagist/l/rap2hpoutre/laravel-log-viewer.svg)](https://packagist.org/packages/rap2hpoutre/laravel-log-viewer) 
[![Packagist](https://img.shields.io/packagist/dm/rap2hpoutre/laravel-log-viewer.svg)](https://packagist.org/packages/rap2hpoutre/laravel-log-viewer) 
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/rap2hpoutre/laravel-log-viewer/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/rap2hpoutre/laravel-log-viewer/?branch=master) 
[![Build Status](https://scrutinizer-ci.com/g/rap2hpoutre/laravel-log-viewer/badges/build.png?b=master)](https://scrutinizer-ci.com/g/rap2hpoutre/laravel-log-viewer/build-status/master)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/7be7a05b07c94f319ec35f95a4d64074)](https://www.codacy.com/app/rap2hpoutre/laravel-log-viewer)
[![Author](https://img.shields.io/badge/author-@rap2h-blue.svg)](https://twitter.com/rap2h)


TL;DR
-----
Log Viewer for Laravel 5 (compatible with 4.2 too) and Lumen. **Install with composer, create a route to `LogViewerController`**. No public assets, no vendor routes, works with and/or without log rotate. Inspired by Micheal Mand's [Laravel 4 log viewer](https://github.com/mikemand/logviewer) (works only with laravel 4.1)

What ?
------
Small log viewer for laravel. Looks like this:

![capture d ecran 2014-12-01 a 10 37 18](https://cloud.githubusercontent.com/assets/1575946/5243642/8a00b83a-7946-11e4-8bad-5c705f328bcc.png)

Install (Laravel)
-----------------
Install via composer
```
composer require rap2hpoutre/laravel-log-viewer
```

Add Service Provider to `config/app.php` in `providers` section
```php
Rap2hpoutre\LaravelLogViewer\LaravelLogViewerServiceProvider::class,
```

Add a route in your web routes file:
```php 
Route::get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');
```

Go to `http://myapp/logs` or some other route

**Optionally** publish `log.blade.php` into `/resources/views/vendor/laravel-log-viewer/` for view customization:

```
php artisan vendor:publish \
  --provider="Rap2hpoutre\LaravelLogViewer\LaravelLogViewerServiceProvider" \
  --tag=views
``` 

Install (Lumen)
---------------

Install via composer
```
composer require rap2hpoutre/laravel-log-viewer
```

Add the following in `bootstrap/app.php`:
```php
$app->register(\Rap2hpoutre\LaravelLogViewer\LaravelLogViewerServiceProvider::class);
```

Explicitly set the namespace in `app/Http/routes.php`:
```php
$app->group(['namespace' => '\Rap2hpoutre\LaravelLogViewer'], function() use ($app) {
    $app->get('logs', 'LogViewerController@index');
});
```

Troubleshooting
---------------

If you got a `InvalidArgumentException in FileViewFinder.php` error, it may be a problem with config caching. Double check installation, then run `php artisan config:clear`.

