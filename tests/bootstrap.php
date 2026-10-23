<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('LOW_OPTIONS_ROOT', dirname(__DIR__));
define('BASEPATH', LOW_OPTIONS_ROOT . '/tests/');
define('PATH_THIRD', LOW_OPTIONS_ROOT . '/');
define('APP_VER', '7.5.0');
define('REQ', 'PAGE');
define('BASE', '/admin.php?S=0');
define('AMP', '&amp;');
define('APPPATH', LOW_OPTIONS_ROOT . '/tests/');
define('SYSPATH', LOW_OPTIONS_ROOT . '/tests/');
define('URL_THIRD_THEMES', '/themes/user/');
define('PATH_CACHE', sys_get_temp_dir() . '/low-options-' . getmypid() . '/');
define('FILE_WRITE_MODE', 0600);
define('DIR_WRITE_MODE', 0700);

require __DIR__ . '/Support/Environment.php';
require __DIR__ . '/Support/Collection.php';
require __DIR__ . '/Support/Fieldtype.php';
require __DIR__ . '/Support/TestCase.php';
require __DIR__ . '/Support/functions.php';
$initialIncludePath = get_include_path();
$initialAutoloaders = spl_autoload_functions() ?: array();
register_shutdown_function(function () use ($initialIncludePath, $initialAutoloaders) {
    set_include_path($initialIncludePath);
    foreach (spl_autoload_functions() ?: array() as $autoloader) {
        if (!in_array($autoloader, $initialAutoloaders, true)) {
            spl_autoload_unregister($autoloader);
        }
    }
});
require LOW_OPTIONS_ROOT . '/low_options/autoload.php';

if (getenv('LOW_OPTIONS_RAW_COVERAGE')) {
    if (!function_exists('xdebug_start_code_coverage')) {
        throw new RuntimeException('Xdebug branch instrumentation is required.');
    }
    xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE | XDEBUG_CC_BRANCH_CHECK);
    register_shutdown_function(function () {
        $coverage = xdebug_get_code_coverage();
        $coverage = array_filter($coverage, function ($file) {
            return strpos($file, LOW_OPTIONS_ROOT . '/low_options/') !== false;
        }, ARRAY_FILTER_USE_KEY);
        file_put_contents(getenv('LOW_OPTIONS_RAW_COVERAGE'), json_encode($coverage, JSON_PRETTY_PRINT));
    });
}
