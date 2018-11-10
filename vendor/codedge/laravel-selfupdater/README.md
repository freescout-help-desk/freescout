# Laravel Application Self-Updater

[![Latest Stable Version](https://poser.pugx.org/codedge/laravel-selfupdater/v/stable?format=flat-square)](https://packagist.org/packages/codedge/laravel-selfupdater)
[![Total Downloads](https://poser.pugx.org/codedge/laravel-selfupdater/downloads?format=flat-square)](https://packagist.org/packages/codedge/laravel-selfupdater)
[![Build Status](https://travis-ci.org/codedge/laravel-selfupdater.svg?branch=master)](https://travis-ci.org/codedge/laravel-selfupdater)
[![StyleCI](https://styleci.io/repos/64463948/shield)](https://styleci.io/repos/64463948)
[![codecov](https://codecov.io/gh/codedge/laravel-selfupdater/branch/master/graph/badge.svg)](https://codecov.io/gh/codedge/laravel-selfupdater)

This package provides some basic methods to implement a self updating
functionality for your Laravel 5 application. Already bundled are some
methods to provide a self-update mechanism via Github.

Usually you need this when distributing a self-hosted Laravel application
that needs some updating mechanism, as you do not want to bother your
lovely users with Git and/or Composer commands ;-)

## Install with Composer

There are currently two branches:
* `master`: Compatible with PHP 7.x
* `5.x`: Compatible with PHP 5.5 + 5.6

_Please select the right branch for your PHP version accordingly._

To install the latest version from the master using [Composer](https://getcomposer.org/):
```sh
$ composer require codedge/laravel-selfupdater
```

This adds the _codedge/laravel-selfupdater_ package to your `composer.json` and downloads the project.

You need to include the service provider in your `config/app.php` `[1]` and optionally the _facade_ `[2]`:
```php
// config/app.php

return [

    //...
    
    'providers' => [
        // ...
        
        Codedge\Updater\UpdaterServiceProvider::class, // [1]
    ],
    
    // ...
    
    'aliases' => [
        // ...
        
        'Updater' => Codedge\Updater\UpdaterManager::class, // [2]

]
```

Additionally add the listener to your `app/Providers/EventServiceProvider.php`:

```php
// app/Providers/EventServiceProvider.php

/**
 * The event handler mappings for the application.
 *
 * @var array
 */
protected $listen = [
    // ...
    
    \Codedge\Updater\Events\UpdateAvailable::class => [
        \Codedge\Updater\Listeners\SendUpdateAvailableNotification::class
    ],
    \Codedge\Updater\Events\UpdateSucceeded::class => [
        \Codedge\Updater\Listeners\SendUpdateSucceededNotification::class
    ],

];

```

## Configuration
After installing the package you need to publish the configuration file via
 ```sh
 $ php artisan vendor:publish --provider="Codedge\Updater\UpdaterServiceProvider"
 ```
 
**Note:** Please enter correct value for vendor and repository name in your `config/self-updater.php` if you want to
use Github as source for your updates.

### Running artisan commands
Artisan commands can be run before or after the update process and can be configured in `config/self-updater.php`:

__Example:__
```php
'artisan_commands' => [
    'pre_update' => [
        'updater:prepare' => [
            'class' => \App\Console\Commands\PreUpdateTasks::class,
            'params' => []
        ],
    ],
    'post_update' => [
        'postupdate:cleanup' => [
            'class' => \App\Console\Commands\PostUpdateCleanup::class,
            'params' => [
                'log' => 1,
                'reset' => false,
                // etc.
            ]
        ]
    ]
]
```

### Notifications via email
You need to specify a recipient email address and a recipient name to receive
update available notifications.
You can specify these values by adding `SELF_UPDATER_MAILTO_NAME` and
`SELF_UPDATER_MAILTO_ADDRESS` to your `.env` file.

| Config name              | Description |
| -----------              | ----------- |
| SELF_UPDATER_MAILTO_NAME | Name of email recipient |
| SELF_UPDATER_MAILTO_ADDRESS    | Address of email recipient |
| SELF_UPDATER_MAILTO_UPDATE_AVAILABLE_SUBJECT | Subject of update available email |
| SELF_UPDATER_MAILTO_UPDATE_SUCCEEDED_SUBJECT | Subject of update succeeded email |

## Usage
To start an update process, i. e. in a controller, just use:
```php
public function update()
{
    // This downloads and install the latest version of your repo
    Updater::update();
    
    // Just download the source and do the actual update elsewhere
    Updater::fetch();
    
    // Check if a new version is available and pass current version
    Updater::isNewVersionAvailable('1.2');
}
```

Of course you can inject the _updater_ via method injection:
```php
public function update(UpdaterManager $updater)
{

    $updater->update(); // Same as above
    
    // .. and shorthand for this:
    $updater->source()->update;
    
    $updater->fetch() // Same as above...
}
```

**Note:** Currently the fetching of the source is a _synchronous_ process.
It is not run in background.

### Using Github
The package comes with a _Github_ source repository type to fetch 
releases from Github - basically use Github to pull the latest version
of your software.

Just make sure you set the proper repository in your `config/self-updater.php`
file.

## Extending and adding new source repository types
You want to pull your new versions from elsewhere? Feel free to create
your own source repository type somewhere but keep in mind for the new
source repository type:

- It _needs to_ extend **AbstractRepositoryType**
- It _needs to_ implement **SourceRepositoryTypeContract**

So the perfect class head looks like
```
class BitbucketRepositoryType extends AbstractRepositoryType implements SourceRepositoryTypeContract
```

Afterwards you may create your own [service provider](https://laravel.com/docs/5.2/providers),
i. e. BitbucketUpdaterServiceProvider, with your boot method like so:

```php
public function boot()
{
    Updater::extend('bitbucket', function($app) {
        return Updater::sourceRepository(new BitbucketRepositoryType);
    });
}

```

Now you call your own update source with:
```php
public function update(UpdaterManager $updater)
{
    $updater->source('bitbucket')->update();
}
```

## Contributing
Please see the [contributing guide](CONTRIBUTING.md).

## Roadmap
Just a quickly sketched [roadmap](https://github.com/codedge/laravel-selfupdater/wiki/Roadmap) what still needs to be implemented.

## Licence
The MIT License (MIT). Please see [Licencse file](LICENSE) for more information.