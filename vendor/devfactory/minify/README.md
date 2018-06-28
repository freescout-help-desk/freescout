# Minify

[![Build Status](https://travis-ci.org/DevFactoryCH/minify.svg)](https://travis-ci.org/DevFactoryCH/minify)
[![Latest Stable Version](https://poser.pugx.org/devfactory/minify/v/stable.svg)](https://packagist.org/packages/devfactory/minify)
[![Total Downloads](https://poser.pugx.org/devfactory/minify/downloads.svg)](https://packagist.org/packages/devfactory/minify)
[![License](https://poser.pugx.org/devfactory/minify/license.svg)](https://packagist.org/packages/devfactory/minify)

With this package you can minify your existing stylessheet and javascript files for laravel 5. This process can be a little tough, this package simplies this process and automates it.

For Larvel 4 please use [ceesvanegmond/minify](https://github.com/ceesvanegmond/minify)

## Installation

Begin by installing this package through Composer.


```js
	{
	    "require": {
	    	"devfactory/minify": "1.0.*"
		}
	}
```


### Laravel installation

Then register the service provider and Facade by opening `config/app.php`

    'Devfactory\Minify\MinifyServiceProvider',

    'Minify'        => 'Devfactory\Minify\Facades\MinifyFacade',


Publish the config file:

```
	php artisan vendor:publish

```

When you've added the ```MinifyServiceProvider``` an extra ```Minify``` facade is available.
You can use this Facade anywhere in your application

#### Stylesheet

```php
	// app/views/hello.blade.php

	<html>
		<head>
			...
			{!! Minify::stylesheet('/css/main.css') !!}
			// or by passing multiple files
			{!! Minify::stylesheet(array('/css/main.css', '/css/bootstrap.css')) !!}
			// add custom attributes
			{!! Minify::stylesheet(array('/css/main.css', '/css/bootstrap.css'), array('foo' => 'bar')) !!}
			// add full uri of the resource
			{!! Minify::stylesheet(array('/css/main.css', '/css/bootstrap.css'))->withFullUrl() !!}
		{!! Minify::stylesheet(array('//fonts.googleapis.com/css?family=Roboto')) !!}

			// minify and combine all stylesheet files in given folder
			{!! Minify::stylesheetDir('/css/') !!}
			// add custom attributes to minify and combine all stylesheet files in given folder
			{!! Minify::stylesheetDir('/css/', array('foo' => 'bar', 'defer' => true)) !!}
			// minify and combine all stylesheet files in given folder with full uri
			{!! Minify::stylesheetDir('/css/')->withFullUrl() !!}
		</head>
		...
	</html>

```

#### Javascript

```php
	// app/views/hello.blade.php

	<html>
		<body>
		...
		</body>
		{!! Minify::javascript('/js/jquery.js') !!}
		// or by passing multiple files
		{!! Minify::javascript(array('/js/jquery.js', '/js/jquery-ui.js')) !!}
		// add custom attributes
		{!! Minify::javascript(array('/js/jquery.js', '/js/jquery-ui.js'), array('bar' => 'baz')) !!}
		// add full uri of the resource
		{!! Minify::javascript(array('/js/jquery.js', '/js/jquery-ui.js'))->withFullUrl() !!}
        {!! Minify::javascript(array('//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js')) !!}

		// minify and combine all javascript files in given folder
		{!! Minify::javascriptDir('/js/') !!}
		// add custom attributes to minify and combine all javascript files in given folder
		{!! Minify::javascriptDir('/js/', array('bar' => 'baz', 'async' => true)) !!}
		// minify and combine all javascript files in given folder with full uri
		{!! Minify::javascriptDir('/js/')->withFullUrl() !!}
	</html>

```

### Config
```php
	<?php

	return array(

    /*
    |--------------------------------------------------------------------------
    | Sort Direction
    |--------------------------------------------------------------------------
    |
    | You can set the sort direction (ascending/descending) when
    | minifying full directories.
    |
    */

    'reverse_sort' => true,

    /*
    |--------------------------------------------------------------------------
    | App environments to not minify
    |--------------------------------------------------------------------------
    |
    | These environments will not be minified and all individual files are
    | returned
    |
    */

    'ignore_environments' => array(
	    'local',
    ),

    /*
    |--------------------------------------------------------------------------
    | CSS build path
    |--------------------------------------------------------------------------
    |
    | Minify is an extension that can minify your css files into one build file.
    | The css_builds_path property is the location where the builded files are
    | stored. This is relative to your public path. Notice the trailing slash.
    | Note that this directory must be writeable.
    |
    */

    'css_build_path' => '/css/builds/',
    'css_url_path' => '/css/builds/',

    /*
    |--------------------------------------------------------------------------
    | JS build path
    |--------------------------------------------------------------------------
    |
    | Minify is an extension that can minify your js files into one build file.
    | The js_build_path property is the location where the builded files are
    | stored. This is relative to your public path. Notice the trailing slash.
    | Note that this directory must be writeable.
    |
    */

    'js_build_path' => '/js/builds/',
    'js_url_path' => '/js/builds/',

	/*
    |--------------------------------------------------------------------------
    | Hash modification
    |--------------------------------------------------------------------------
    |
    | You can disable usage of modification time (mtime) for hash build and
	| add additional salt (exp. commit hash) for hash build
    |
    */

    'disable_mtime' => false,
    'hash_salt' => '',

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | You can set the base URL for the links generated with the configuration
    | value. By default if empty HTTP_HOST would be used.
    |
    */
	'base_url' => ''

	);
```
