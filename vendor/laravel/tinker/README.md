<p align="center"><img src="https://laravel.com/assets/img/components/logo-tinker.svg"></p>

<p align="center">
<a href="https://travis-ci.org/laravel/tinker"><img src="https://travis-ci.org/laravel/tinker.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/tinker"><img src="https://poser.pugx.org/laravel/tinker/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/tinker"><img src="https://poser.pugx.org/laravel/tinker/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/tinker"><img src="https://poser.pugx.org/laravel/tinker/license.svg" alt="License"></a>
</p>

## Introduction

Laravel Tinker is a powerful REPL for the Laravel framework.

## Installation

To get started with Laravel Tinker, simply run:

    composer require laravel/tinker

If you are using Laravel 5.5+, there is no need to manually register the service provider. However, if you are using an earlier version of Laravel, register the `TinkerServiceProvider` in your `app` configuration file:

```php
'providers' => [
    // Other service providers...

    Laravel\Tinker\TinkerServiceProvider::class,
],
```

## Basic Usage

From your console, execute the `php artisan tinker` command.

## License

Laravel Tinker is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
