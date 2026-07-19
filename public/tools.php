<?php
/**
 * Application installer.
 */

define('ALLOWED_PHP_DIRS', [
    '/usr/bin',
    '/usr/local/bin',
    '/usr/sbin',
    '/usr/local/sbin',
]);

ini_set('display_errors', 'Off');

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
    'symfony/http-foundation/Request.php',
    'symfony/http-foundation/ParameterBag.php',
    'symfony/http-foundation/FileBag.php',
    'symfony/http-foundation/ServerBag.php',
    'symfony/http-foundation/HeaderBag.php',
];
foreach ($vendor_files as $vendor_file) {
    if (file_exists($root_dir.'vendor/'.$vendor_file)) {
        require_once $root_dir.'vendor/'.$vendor_file;
    } else {
        require_once $root_dir.'overrides/'.$vendor_file;
    }
}

function throttle($max_requests = 10, $window_seconds = 60)
{
    $now = time();

    // Create request from global PHP variables.
    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $ip = $request->getClientIp();

    $file = (sys_get_temp_dir() ?: '/tmp') . '/fs_tools_throttle_' . sha1($ip) . '.json';

    $events = [];

    if (file_exists($file)) {
        $events = json_decode(file_get_contents($file), true) ?: [];
    }

    // Keep only timestamps inside the window
    $filtered = [];
    for ($i = 0; $i < count($events); $i++) {
        if (($now - $events[$i]) < $window_seconds) {
            $filtered[] = $events[$i];
        }
    }

    $events = $filtered;

    // Too many requests.
    if (count($events) >= $max_requests) {
        return true;
    }

    // Add current request
    $events[] = $now;

    file_put_contents($file, json_encode($events), LOCK_EX);
}

// Get app key
//function getAppKey($root_dir, $check_cache = true)
function getEnvVar($var_name, $root_dir)
{
    // First check APP_KEY in cache
    // if ($check_cache && file_exists($root_dir.'bootstrap/cache/config.php')) {
    //     $config = include $root_dir.'bootstrap/cache/config.php';

    //     if (!empty($config)) {
    //         if (!empty($config['app']['key'])) {
    //             return $config['app']['key'];
    //         } else {
    //             return '';
    //         }
    //     }
    // }

    // Read .env file into $_ENV
    try {
        $dotenv = new Dotenv\Dotenv($root_dir);
        // If using load() if $_ENV['APP_KEY'] was present in .env before it will not be updated when reading
        $dotenv->overload();
    } catch (\Exception $e) {
        // Do nothing
    }

    if (!empty($_ENV[$var_name])) {
        return $_ENV[$var_name];
    } else {
        return '';
    }
}

// Set PHP_PATH in the .env file if PHP on your server can not be executed via "php" console command.
// https://github.com/freescout-help-desk/freescout/issues/5507
define('PHP_PATH', trim(getEnvVar('PHP_PATH', $root_dir)));

function clearCache($root_dir, $php_path)
{
    if (file_exists($root_dir.'bootstrap/cache/config.php')) {
        unlink($root_dir.'bootstrap/cache/config.php');
    }
    if (file_exists($root_dir.'bootstrap/cache/services.php')) {
        unlink($root_dir.'bootstrap/cache/services.php');
    }
    if (file_exists($root_dir.'bootstrap/cache/packages.php')) {
        unlink($root_dir.'bootstrap/cache/packages.php');
    }
    if (file_exists($root_dir.'bootstrap/cache/routes.php')) {
        unlink($root_dir.'bootstrap/cache/routes.php');
    }
    return shell_exec($php_path.' '.$root_dir.'artisan freescout:clear-cache');
}

$logged_in = false;
$alerts = [];
$errors = [];
$app_key = trim($_POST['app_key'] ?? '');
$db_username = trim($_POST['db_username'] ?? '');
$db_password = trim($_POST['db_password'] ?? '');

if (!empty($_POST)) {

    if (throttle()) {
        http_response_code(429);
        exit('Too many requests. Please wait.');
    }

    // First check DB credentials.
    $env_db_username = trim(getEnvVar('DB_USERNAME', $root_dir));
    $env_db_password = trim(getEnvVar('DB_PASSWORD', $root_dir));

    if (($db_username !== $env_db_username && $db_username != (string)sha1($env_db_username))
        || ($db_password !== $env_db_password && $db_password != (string)sha1($env_db_password))
    ) {
        $alerts[] = [
            'type' => 'danger',
            'text' => 'Invalid DB Username or Password',
        ];
    } else {
        $logged_in = true;
    }

    if ($logged_in && $app_key) {
        $php_path = PHP_PATH ?: 'php';

        // https://github.com/freescout-help-desk/freescout/security/advisories/GHSA-jx2w-fhmw-rg39
        if (!empty($_POST['php_path'])) {
            $php_path = trim($_POST['php_path']);

            $php_path = preg_replace("#[ ;\$<>:&\|`\t\r\n]#", '', $php_path);
            if (!$php_path) {
                $php_path = 'php';
            }

            // Is allowed PHP directory.
            if (!PHP_PATH) {
                $real_php_dir = dirname(realpath($php_path));

                if ($real_php_dir && !in_array($real_php_dir, ALLOWED_PHP_DIRS, true)) {
                    $errors['php_path'] = 'Directory is not allowed. Allowed directories: '.implode(', ', ALLOWED_PHP_DIRS).". You may need to set PHP_PATH parameter in /public/tools.php";
                }
            }

            // Sanitize path.
            // https://github.com/freescout-helpdesk/freescout/security/advisories/GHSA-7p9x-ch4c-vqj9
            if (empty($errors['php_path'])) {
                if (!file_exists($php_path) || !stristr($php_path, 'php')) {
                    $errors['php_path'] = 'Invalid Path to PHP';
                }
            }
        }

        if (empty($errors)) {
            if (trim($app_key) !== trim(getEnvVar('APP_KEY', $root_dir))) {
                $errors['app_key'] = 'Invalid App Key';
            } else {
                if (!function_exists('shell_exec')) {
                    $alerts[] = [
                        'type' => 'danger',
                        'text' => '<code>shell_exec</code> function is unavailable. Can not run updating.',
                    ];
                } else {

                    // Make sure that it's actually $php_path points to PHP executable and not something else.
                    $version_output = shell_exec($php_path.' -r "echo phpversion();"');

                    if (!preg_match("#^\d+\.\d+\.\d+#", $version_output)) {
                        if ($php_path != 'php') {
                            // $alerts[] = [
                            //     'type' => 'danger',
                            //     'text' => 'Invalid Path to PHP: '.$php_path,
                            // ];
                            $errors['php_path'] = 'Invalid Path to PHP';
                        } else {
                            $alerts[] = [
                                'type' => 'danger',
                                'text' => '"php" command could not be executed. You may need to set PHP_PATH parameter in /public/tools.php',
                            ];
                        }
                    }

                    if (!count($alerts) && empty($errors)) {
                        if ($_POST['action'] == 'cc') {
                            $cc_output = clearCache($root_dir, $php_path);

                            $alerts[] = [
                                'type' => 'success',
                                'text' => 'Cache cleared: <br/><pre>'.htmlspecialchars($cc_output).'</pre>',
                            ];
                        } else {
                            try {
                                // First check PHP version.
                                if (!version_compare($version_output, '7.1', '>=')) {
                                    $alerts[] = [
                                        'type' => 'danger',
                                        'text' => 'Incorrect PHP version (7.1+ is required):<br/><br/><pre>'.htmlspecialchars($version_output).'</pre>',
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
                                    'text' => 'Error occurred: '.htmlspecialchars($e->getMessage()),
                                ];
                            }
                        }
                    }
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

                        <?php if (!$logged_in): ?>
                            <div class="form-group <?php if (!empty($errors['db_username'])):?>has-error<?php endif ?>">
                                <label for="db_username">
                                    <strong>DB Username</strong> (DB_USERNAME from .env file)
                                </label>
                                <input type="text" name="db_username" value="<?php echo htmlentities($db_username); ?>" required="required"/>
                            </div>
                            <div class="form-group <?php if (!empty($errors['db_password'])):?>has-error<?php endif ?>">
                                <label for="db_password">
                                    <strong>DB Password</strong> (DB_PASSWORD from .env file)
                                </label>
                                <input type="password" name="db_password" value="<?php echo htmlentities($db_password); ?>" required="required"/>
                            </div>
                           
                            <div class="buttons">
                                <button class="button" type="submit" name="continue" value="continue">Continue</button>
                            </div>
                        <?php else : ?>

                            <input type="hidden" name="db_username" value="<?php echo ($app_key ? $db_username : sha1($db_username)); ?>" />
                            <input type="hidden" name="db_password" value="<?php echo ($app_key ? $db_password : sha1($db_password)); ?>" />

    						<div class="form-group <?php if (!empty($errors['app_key'])):?>has-error<?php endif ?>">
    		                    <label for="app_key">
    		                        <small style="color:red">*</small> <strong>App Key</strong> (APP_KEY from .env file)
    		                    </label>
    		                    <input type="password" name="app_key" value="<?php echo htmlentities($app_key); ?>" required="required"/>
    		                    <?php if (!empty($errors['app_key'])): ?>
    		                        <span class="error-block">
    		                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
    		                            <?php echo htmlspecialchars($errors['app_key']); ?>
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
    		                            <?php echo htmlspecialchars($errors['php_path']); ?>
    		                        </span>
    		                    <?php endif ?>
    		                </div>
    		                <div class="buttons">
                                <button class="button" type="submit" name="action" value="cc">
                                    Clear Cache
                                </button>
    		                    <br/>
                                <button class="button" type="submit" name="action" value="update">
                                    Update Now
                                </button>
    		                    <button class="button" type="submit" name="action" value="migrate">
    		                        Migrate DB
    		                    </button>
    		                </div>
                        <?php endif ?>
	                </form>
               	</div>
            </div>
        </div>
    </body>
</html>