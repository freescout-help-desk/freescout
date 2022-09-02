<?php
/**
 * Application installer.
 */
ini_set('display_errors', 'Off');

if (preg_match("#^/public\/(.*)#", $_SERVER['REQUEST_URI'], $m) && !empty($m[1])) {
    header("Location: /".$m[1]);
    exit();
}

$required_functions = ['putenv', 'symlink'];

foreach ($required_functions as $required_function) {
    if (!function_exists($required_function)) {
        echo $required_function.'() function is required - make sure to enabled it in your PHP.';
        exit();
    }
}

$root_dir = realpath(__DIR__.'/..').'/';

// Dotenv library for reading .env files
$vendor_files = [
    'vlucas/phpdotenv/src/Dotenv.php',
    'vlucas/phpdotenv/src/Loader.php',
    'vlucas/phpdotenv/src/Validator.php',
    'vlucas/phpdotenv/src/Exception/ExceptionInterface.php',
    'vlucas/phpdotenv/src/Exception/InvalidCallbackException.php',
    'vlucas/phpdotenv/src/Exception/InvalidFileException.php',
    'vlucas/phpdotenv/src/Exception/InvalidPathException.php',
    'vlucas/phpdotenv/src/Exception/ValidationException.php',
];
foreach ($vendor_files as $vendor_file) {
    if (file_exists($root_dir.'vendor/'.$vendor_file)) {
        require_once $root_dir.'vendor/'.$vendor_file;
    } else {
        require_once $root_dir.'overrides/'.$vendor_file;
    }
}

// Symfony proces
//require_once($root_dir.'vendor/symfony/process/Process.php');

// Laravel Encrypter
// require_once($root_dir.'vendor/laravel/framework/src/Illuminate/Contracts/Encryption/Encrypter.php');
// require_once($root_dir.'vendor/laravel/framework/src/Illuminate/Encryption/Encrypter.php');

function generateRandomKey()
{
    return 'base64:'.base64_encode(
        //Encrypter::generateKey('AES-256-CBC');
        random_bytes(32)
    );
}

function writeNewEnvironmentFileWith($key, $environmentFilePath)
{
    file_put_contents($environmentFilePath, preg_replace(
        '/^APP_KEY=/m',
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

function showError($msg)
{
    echo <<<HTML
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>FreeScout Installer</title>
        <link href="/css/fonts.css" rel="stylesheet"/>
        <link href="/installer/css/fontawesome.css" rel="stylesheet"/>
        <link href="/installer/css/style.min.css" rel="stylesheet"/>
    </head>
    <body>
    	<div class="master">
            <div class="box">
                <div class="header">
                    <h1 class="header__title">FreeScout Installer</h1>
                </div>
                <div class="main">
                	$msg
               	</div>
            </div>
        </div>
    </body>
</html>
HTML;
}

function showPermissionsError()
{
    $root_dir_no_slash = realpath(__DIR__.'/..');

    showError('Web installer could not write data into <strong>'.$root_dir_no_slash.'/.env</strong> file. Please give your web server user (<strong>'.get_current_user().'</strong>) write permissions in <code>'.$root_dir_no_slash.'</code> folder:<br/><br/>
<textarea rows="4" readonly="readonly" style="font-size:12px;">sudo chgrp '.get_current_user().' '.$root_dir_no_slash.'
sudo chmod ug+rwx '.$root_dir_no_slash.'</textarea><br/>If it does not help, please follow <a href="http://freescout.net/install/#82-manual-installation" target="_blank">Manual installation</a> instructions.');
}

function getSubdirectory()
{
    $subdirectory = $_SERVER['PHP_SELF'];

    $filename = basename($_SERVER['SCRIPT_FILENAME']);

    if (basename($_SERVER['SCRIPT_NAME']) === $filename) {
        $subdirectory = $_SERVER['SCRIPT_NAME'];
    } elseif (basename($_SERVER['PHP_SELF']) === $filename) {
        $subdirectory = $_SERVER['PHP_SELF'];
    } elseif (basename($_SERVER['ORIG_SCRIPT_NAME']) === $filename) {
        $subdirectory = $_SERVER['ORIG_SCRIPT_NAME']; // 1and1 shared hosting compatibility
    } else {
        // Backtrack up the script_filename to find the portion matching
        // php_self
        $path = $_SERVER['PHP_SELF'];
        $file = $_SERVER['SCRIPT_FILENAME'];
        $segs = explode('/', trim($file, '/'));
        $segs = array_reverse($segs);
        $index = 0;
        $last = \count($segs);
        $subdirectory = '';
        do {
            $seg = $segs[$index];
            $subdirectory = '/'.$seg.$subdirectory;
            ++$index;
        } while ($last > $index && (false !== $pos = strpos($path, $subdirectory)) && 0 != $pos);
    }

    $subdirectory = str_replace('public/install.php', '', $subdirectory);
    $subdirectory = str_replace('install.php', '', $subdirectory);

    if (!$subdirectory) {
        $subdirectory = '/';
    }

    return $subdirectory;
}

$app_key = getAppKey($root_dir);

// Generate APP_KEY
if (empty($app_key)) {
    // Copy .env.example
    if (!file_exists($root_dir.'.env')) {
        
        // Check if .env.example eixists
        if (!file_exists($root_dir.'.env.example')) {
            showError('File <strong>'.$root_dir.'.env.example</strong> not found. Please make sure to copy this file from the application dist.');
            exit();
        }

        copy($root_dir.'.env.example', $root_dir.'.env');

        if (!file_exists($root_dir.'.env')) {
            //echo 'Please copy <code>.env.example</code> file to <code>.env</code> and reload this page.';
            showPermissionsError();
            exit();
        }
    }

    // Add APP_KEY= to the .env file if needed
    // Without APP_KEY= the key will not be generated
    if (!preg_match('/^APP_KEY=/m', file_get_contents($root_dir.'.env'))) {
        $append_result = file_put_contents($root_dir.'.env', PHP_EOL.'APP_KEY=', FILE_APPEND);
        if (!$append_result) {
            //showError('Could not write APP_KEY to .env file. Please run the following commands in SSH console:<br/><code>php artisan key:generate</code><br/><code>php artisan freescout:clear-cache</code>');
            showPermissionsError();
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
    header('Location: '.getSubdirectory().'install');
} else {
    showPermissionsError();
}
exit();
