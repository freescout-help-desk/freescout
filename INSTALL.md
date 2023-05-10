# CaniDesk

## Table of Contents

* [Prerequisites](#Prerequisites)
* [Installation](#Installation)
* [Development](#Development)


## Prerequisites

* PHP 7.4
* MySQL Server 8.0
* Composer
* Node.js 16
* MailHog


## Installation

1. Clone this repo.
1. Move to the application root:
    ``` bash
    $ cd canidesk
    ```
1. Check out a target branch you will work on.
1. Create the application environment variables file:
    ``` bash
    $ cp .env.example .env
    ```
1. Create a database and its user on your MySQL server.
1. Run MailHog.
1. According to your setup, update environment variables in .env:
    ``` ini
    # Edit .env

    APP_ENV=local # APP_ENV=pre for Pre env, APP_ENV=prod for Production env

    APP_LOCALE=en

    DB_DATABASE=canidesk_db # Database name
    DB_USERNAME=root # Database user
    DB_PASSWORD=root # Database user password
    ```
1. Complete the remaining steps on your terminal:
    ``` bash
    # Install PHP dependencies
    $ composer install

    # Generate APP_KEY
    $ php artisan key:generate

    # Install Telescope (Debugger for Laravel: Optional)
    $ php artisan telescope:install

    # Initialize DB
    $ php artisan migrate:fresh
    $ php artisan db:seed
    ```


## Development

* Start a local web server:
    ``` bash
    $ php artisan serve # Ctrl+C to stop
    ```
* To migrate new DB changes, run:
    ``` bash
    $ php artisan migrate
    ```
* To build frontend code when you made changes or checked out a branch, run:
    ``` bash
    $ npm run dev
    ```
    OR make that build operation hands-free with:
    ``` bash
    $ npm run watch # Ctrl+C to stop watching
    ```
* To clear all the local cache, run:
    ``` bash
    $ composer clear-cache && php artisan cache:clear && php artisan config:clear && php artisan queue:clear && php artisan queue:clear --queue=csv && php artisan queue:flush && php artisan telescope:clear
    ```
* To use the Tinker environment (REPL for Laravel), run:
    ``` bash
    $ php artisan tinker
    >>> App\Models\Team::all() # For example this returns the team list
    >>> exit
    ```