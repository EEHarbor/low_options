<?php

$root = dirname(__DIR__, 2);
define('LOW_OPTIONS_ROOT', $root);
$mode = $argv[1];
if ($mode === 'fatal') {
    throw new RuntimeException('owned native failure');
}
if ($mode === 'exit') {
    exit(7);
}
if ($mode === 'caught') {
    try {
        @trigger_error('owned caught native diagnostic', E_USER_DEPRECATED);
    } catch (ErrorException $error) {
        // The checked receipt must remain authoritative after a caller catches it.
    }
}
if ($mode === 'nested') {
    require_once $root . '/tests/Support/NativeProcess.php';
    $result = LowOptionsTests\NativeProcess::run(__FILE__, array('healthy'));
    if ($result !== array(0, "native\n", '')) {
        throw new RuntimeException('Nested native result changed.');
    }
}
if ($mode === 'standalone') {
    require_once $root . '/tests/Support/NativeProcess.php';
    echo json_encode(LowOptionsTests\NativeProcess::run(__FILE__, array($argv[2])), JSON_THROW_ON_ERROR), "\n";
    exit(0);
}
$metadata = require $root . '/low_options/addon.setup.php';
if ($metadata['namespace'] !== 'Low\\Options') {
    throw new RuntimeException('The distributed add-on metadata did not execute.');
}
echo "native\n";
