<?php
/**
 * Application installer.
 */

ini_set('display_errors', 'Off');

$root_dir = realpath(__DIR__.'/..').'/';

// Dotenv library for reading .env files
require_once($root_dir.'vendor/vlucas/phpdotenv/src/Dotenv.php');
require_once($root_dir.'vendor/vlucas/phpdotenv/src/Loader.php');
require_once($root_dir.'vendor/vlucas/phpdotenv/src/Validator.php');
require_once($root_dir.'vendor/vlucas/phpdotenv/src/Exception/ExceptionInterface.php');
require_once($root_dir.'vendor/vlucas/phpdotenv/src/Exception/InvalidCallbackException.php');
require_once($root_dir.'vendor/vlucas/phpdotenv/src/Exception/InvalidFileException.php');
require_once($root_dir.'vendor/vlucas/phpdotenv/src/Exception/InvalidPathException.php');
require_once($root_dir.'vendor/vlucas/phpdotenv/src/Exception/ValidationException.php');
// Symfony proces
//require_once($root_dir.'vendor/symfony/process/Process.php');

// Laravel Encrypter
// require_once($root_dir.'vendor/laravel/framework/src/Illuminate/Contracts/Encryption/Encrypter.php');
// require_once($root_dir.'vendor/laravel/framework/src/Illuminate/Encryption/Encrypter.php');

function generateRandomKey()
{
    return 'base64:'.base64_encode(
        //Encrypter::generateKey('AES-256-CBC');
        random_bytes($cipher == 'AES-128-CBC' ? 16 : 32)
    );
}

function writeNewEnvironmentFileWith($key, $environmentFilePath)
{
	file_put_contents($environmentFilePath, preg_replace(
        "/^APP_KEY=/m",
        'APP_KEY='.$key,
        file_get_contents($environmentFilePath)
    ));
}

// Get app key 
function getAppKey($root_dir, $check_cache = true)
{
	// First check APP_KEY in cache
	if ($check_cache && file_exists($root_dir.'bootstrap/cache/config.php')) {
		$config = include $root_dir.'bootstrap/cache/config.php';

		if (!empty($config)) {
			if (!empty($config['app']['key'])) {
				return $config['app']['key'];
			} else {
				return '';
			}
		}
	}

	// Read .env file into $_ENV
	try {
		$dotenv = new Dotenv\Dotenv($root_dir);
		// If using load() if $_ENV['APP_KEY'] was present in .env before it will not be updated when reading 
		$dotenv->overload();
	} catch (\Exception $e) {
		// Do nothing
	}

	if (!empty($_ENV['APP_KEY'])) {
		return $_ENV['APP_KEY'];
	} else {
		return '';
	}
}

function clearCache($root_dir)
{
	if (file_exists($root_dir.'bootstrap/cache/config.php')) {
		unlink($root_dir.'bootstrap/cache/config.php');
	}
}

$app_key = getAppKey($root_dir);

// Generate APP_KEY
if (empty($app_key)) {
	// Copy .env.example
	if (!file_exists($root_dir.'.env')) {
		copy($root_dir.'.env.example', $root_dir.'.env');

		if (!file_exists($root_dir.'.env')) {
			//echo 'Please copy <code>.env.example</code> file to <code>.env</code> and reload this page.';
			$root_dir_no_slash = realpath(__DIR__.'/..');
			echo 'Web installer will need to create <code>.env</code> file in the root folder of your application. Please run the following commands in SSH console to allow the web server to write to the root folder:<br/><br/>
<code>sudo chgrp '.get_current_user().' '.$root_dir_no_slash.'<br/>
sudo chmod ug+rwx '.$root_dir_no_slash.'</code>';
			exit();
		}
	}

	// Add APP_KEY= to the .env file if needed
	// Without APP_KEY= the key will not be generated
	if (!preg_match("/^APP_KEY=/m", file_get_contents($root_dir.'.env'))) {
		$append_result = file_put_contents($root_dir.'.env', PHP_EOL.'APP_KEY=', FILE_APPEND);
		if (!$append_result) {
			echo 'Could not write APP_KEY to .env file. Please run the following commands in SSH console:<br/><code>php artisan key:generate</code><br/><code>php artisan freescout:clear-cache</code>';
			exit();
		}
	}

	writeNewEnvironmentFileWith(generateRandomKey(), $root_dir.'.env');

	// Clear cache
	// We have to clear cache to avoid infinite redirects
	clearCache($root_dir);
	
	$app_key = getAppKey($root_dir, false);
}

if (!empty($app_key)) {
	// When APP_KEY generated, redirect to /install
	header("Location: /install");
} else {
	echo 'Please run the following commands in SSH console:<br/><code>php artisan key:generate</code><br/><code>php artisan freescout:clear-cache</code>';
}
exit();