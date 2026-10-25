<?php

$state = $argv[1];
$root = sys_get_temp_dir() . '/optional-updater/';
mkdir($root . 'low_options', 0700, true);
$extension = $root . 'low_options/ext.low_options.php';
if ($state !== 'missing') {
    file_put_contents($extension, '<?php class Owned_updater_ext { public function updateExtension($current) { $GLOBALS["owned_extension_calls"]++; } }');
    chmod($extension, $state === 'unreadable' ? 0000 : 0600);
}
define('PATH_THIRD', $root);
$GLOBALS['owned_extension_calls'] = 0;
try {
    require 'phar://' . dirname(__DIR__, 2) . '/low_options/low_options.phar/FluxCapacitor/Base/Upd.php';
    $updater = (new ReflectionClass('Low\\Options\\FluxCapacitor\\Base\\Upd'))->newInstanceWithoutConstructor();
    $updater->version = '2.0.0';
    $updater->module_name = 'Owned_updater';
    $updated = $updater->update('1.0.0');
    echo json_encode(array('updated' => $updated, 'extension_calls' => $GLOBALS['owned_extension_calls']), JSON_THROW_ON_ERROR), "\n";
} finally {
    if (is_file($extension)) {
        chmod($extension, 0600);
    }
}
