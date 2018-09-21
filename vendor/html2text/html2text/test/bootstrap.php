<?php
/*
 * Our test cases rely on PSR-4 autoloading, but no autoloader is
 * shipped with the library. Composer will create one, but if the
 * library is installed in some other manner, the test suite won't
 * run. By using this file to bootstrap PHPUnit, we allow the test
 * suite to run out-of-the-box for distributions and users who prefer
 * not to use Composer.
 */
require('src/Html2Text.php');
?>
