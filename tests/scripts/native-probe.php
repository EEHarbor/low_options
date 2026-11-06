<?php

error_reporting(-1);
ini_set('display_errors', 'stderr');

$root = dirname(__DIR__, 2);
$mode = isset($argv[1]) ? $argv[1] : 'load';
$version = isset($argv[2]) ? $argv[2] : '7.5.0';

if ($mode === 'guard') {
    require $root . '/low_options/pi.low_options.php';
    exit(20);
}

define('LOW_OPTIONS_ROOT', $root);
define('BASEPATH', $root . '/tests/');
define('PATH_THIRD', getenv('LOW_OPTIONS_THIRD') ?: $root . '/');
define('APP_VER', $version);
define('REQ', 'PAGE');
define('SYSPATH', $root . '/tests/');
define('APPPATH', $root . '/tests/');
define('PATH_CACHE', sys_get_temp_dir() . '/cache/');
require $root . '/tests/Support/Environment.php';
require $root . '/tests/Support/Fieldtype.php';
require $root . '/tests/Support/functions.php';
$GLOBALS['low_options_ee'] = new \LowOptionsTests\Environment();

require_once dirname(__DIR__) . '/Support/Diagnostics.php';
set_error_handler(\LowOptionsTests\Diagnostics::handler());

try {
    if ($mode === 'no-phar') {
        stream_wrapper_unregister('phar');
    }
    require $root . '/low_options/autoload.php';
    if ($mode === 'unrelated') {
        $result = class_exists('Unrelated\\Missing');
    } else {
        $symbol = isset($argv[3]) ? $argv[3] : 'Base\\Pi';
        $result = class_exists('Low\\Options\\FluxCapacitor\\' . $symbol);
    }
    $data = array('loaded' => $result);
    if ($mode === 'selection') {
        $data['parent'] = get_parent_class('Low\\Options\\FluxCapacitor\\Bridge\\EE');
    }
    echo json_encode($data);
} catch (Throwable $error) {
    fwrite(STDERR, get_class($error) . ': ' . $error->getMessage() . ' at ' . $error->getFile() . ':' . $error->getLine() . "\n");
    exit(1);
}
