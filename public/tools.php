<?php
/**
 * Application installer.
 */
ini_set('display_errors', 'On');

if (preg_match("#^/public\/(.*)#", $_SERVER['REQUEST_URI'], $m) && !empty($m[1])) {
    header("Location: /".$m[1]);
    exit();
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

function clearCache($root_dir, $php_path)
{
    if (file_exists($root_dir.'bootstrap/cache/config.php')) {
        unlink($root_dir.'bootstrap/cache/config.php');
    }
    return shell_exec($php_path.' '.$root_dir.'artisan freescout:clear-cache');
}

$alerts = [];
$errors = [];
$app_key = $_POST['app_key'] ?? '';

if (!empty($_POST)) {

    $php_path = 'php';
    if (!empty($_POST['php_path'])) {
        $php_path = $_POST['php_path'];
    }

    if (trim($app_key) != trim(getAppKey($root_dir))) {
        $errors['app_key'] = 'Invalid App Key';
    } else {
        $cc_output = clearCache($root_dir, $php_path);
        if ($_POST['action'] == 'cc') {
            $alerts[] = [
                'type' => 'success',
                'text' => 'Cache cleared: <br/><pre>'.htmlspecialchars($cc_output).'</pre>',
            ];
        } else {
            if (!function_exists('shell_exec')) {
                $alerts[] = [
                    'type' => 'danger',
                    'text' => '<code>shell_exec</code> function is unavailable. Can not run updating.',
                ];
            } else {
                try {

                    // First check PHP version
                    $version_output = shell_exec($php_path.' -v');

                    if (!strstr($version_output, 'PHP 7.')) {
                        $alerts[] = [
                            'type' => 'danger',
                            'text' => 'Incorrect PHP version (7.x is required):<br/><br/><pre>'.htmlspecialchars($version_output).'</pre>',
                        ];
                    } else {
                        if ($_POST['action'] == 'update') {
                            // Update Now
                            $output = shell_exec($php_path.' '.$root_dir.'artisan freescout:update --force');
                            if (strstr($output, 'Broadcasting queue restart signal')) {
                                $alerts[] = [
                                    'type' => 'success',
                                    'text' => 'Updating finished:<br/><pre>'.htmlspecialchars($output).'</pre>',
                                ];
                            } else {
                                $alerts[] = [
                                    'type' => 'danger',
                                    'text' => 'Something went wrong... Please <strong><a href="https://freescout.net/download/" target="_blank">download</a></strong> the latest version and extract it into your application folder replacing existing files. After that click "Migrate DB" button.<br/><br/><pre>'.htmlspecialchars($output).'</pre>',
                                ];
                            }
                        } else {
                            // Migreate DB
                            $output = shell_exec($php_path.' '.$root_dir.'artisan migrate --force');
                            $alerts[] = [
                                'type' => 'success',
                                'text' => 'Migrating finished:<br/><br/><pre>'.htmlspecialchars($output).'</pre>',
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    $alerts[] = [
                        'type' => 'danger',
                        'text' => 'Error occured: '.htmlspecialchars($e->getMessage()),
                    ];
                }
            }
        }
    }
}

?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>FreeScout Tools</title>
        <link href="/css/fonts.css" rel="stylesheet"/>
        <link href="/installer/css/fontawesome.css" rel="stylesheet"/>
        <link href="/installer/css/style.min.css" rel="stylesheet"/>
    </head>
    <body>
    	<div class="master">
            <div class="box">
                <div class="header">
                    <h1 class="header__title">FreeScout Tools</h1>
                </div>
                <div class="main">

                	<?php if (!empty($alerts)): ?>
                		<?php foreach ($alerts as $alert): ?>
                			<div class="alert alert-<?php echo $alert['type'] ?>">
                				<?php echo $alert['text']; ?>
                			</div>
                		<?php endforeach ?>
                	<?php endif ?>

                	<form method="post" action="">
						<div class="form-group <?php if (!empty($errors['app_key'])):?>has-error<?php endif ?>">
		                    <label for="app_key">
		                        <strong>App Key</strong> (from .env file)
		                    </label>
		                    <input type="text" name="app_key" value="<?php echo htmlentities($app_key); ?>" required="required"/>
		                    <?php if (!empty($errors['app_key'])): ?>
		                        <span class="error-block">
		                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
		                            <?php echo $errors['app_key']; ?>
		                        </span>
		                    <?php endif ?>
		                </div>
						<div class="form-group <?php if (!empty($errors['php_path'])):?>has-error<?php endif ?>">
		                    <label for="php_path">
		                        <strong>Path to PHP</strong> (example: /usr/local/php81/bin/php)
		                    </label>
		                    <input type="text" name="php_path" value="<?php echo htmlentities($_POST['php_path'] ?? ''); ?>" placeholder="(optional)"/>
		                    <?php if (!empty($errors['php_path'])): ?>
		                        <span class="error-block">
		                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
		                            <?php echo $errors['php_path']; ?>
		                        </span>
		                    <?php endif ?>
		                </div>
		                <div class="buttons">
		                    <button class="button" type="submit" name="action" value="update">
		                        Update Now
		                    </button>
		                    <br/>
		                    <button class="button" type="submit" name="action" value="cc">
		                        Clear Cache
		                    </button>
		                    <button class="button" type="submit" name="action" value="migrate">
		                        Migrate DB
		                    </button>
		                </div>
	                </form>
               	</div>
            </div>
        </div>
    </body>
</html>