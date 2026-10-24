<?php

$mode = $argv[1];
$location = $argv[2];
$directory = sys_get_temp_dir();
$outside = $directory . '/outside';
mkdir($outside, 0700);
file_put_contents($outside . '/.sentinel', 'retained');
$container = $directory . '/pharextract';
$target = $container;
if ($location !== 'container') {
    mkdir($container, 0700);
    $target .= '/low_options';
}
if ($location === 'backslash') {
    symlink($outside, $target);
    $target .= '\\';
    mkdir($target, 0700);
    file_put_contents($target . '/.stale', 'remove');
} else {
    symlink($location === 'dangling' ? $outside . '/absent' : $outside, $target);
}
$original = getcwd();
$blocked = false;
try {
    if ($mode === 'fallback') {
        stream_wrapper_unregister('phar');
    }
    require dirname(__DIR__, 2) . '/low_options/low_options.phar';
    if ($mode === 'cleanup') {
        Extract_low_options::_removeTmpFiles($location === 'trailing' ? $target . '/' : $target, $original);
    }
    if ($mode === 'go') {
        Extract_low_options::go(true);
    }
} catch (RuntimeException $exception) {
    if (strpos($exception->getMessage(), 'Unsafe phar extraction ') !== 0) {
        throw $exception;
    }
    $blocked = true;
}
echo json_encode(array(
    'blocked' => $blocked,
    'sentinel' => file_get_contents($outside . '/.sentinel'),
    'link' => is_link($target),
    'cwd_preserved' => getcwd() === $original,
    'target_removed' => $mode !== 'cleanup' || !file_exists($target),
    'sibling_preserved' => $location !== 'backslash' || is_link(substr($target, 0, -1)),
), JSON_THROW_ON_ERROR);
