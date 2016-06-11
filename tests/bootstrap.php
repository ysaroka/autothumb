<?php
/**
 * Created: 2016-06-11
 * @author Yauhen Saroka <yauhen.saroka@gmail.com>
 */

// ensure we get report on all possible php errors
error_reporting(-1);

define('ROOT_DIR', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
define('TEST_DIR', realpath(__DIR__) . DIRECTORY_SEPARATOR);

$_SERVER['SCRIPT_NAME'] = '/' . __DIR__;
$_SERVER['SCRIPT_FILENAME'] = __FILE__;

// require composer autoloader if available
$composerAutoload = ROOT_DIR . 'vendor/autoload.php';

if (!file_exists($composerAutoload)) {
    throw new RuntimeException('Dependencies not installed.');
}
