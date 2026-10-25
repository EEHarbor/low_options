<?php

require dirname(__DIR__, 2) . '/low_options/low_options.phar';
$root = realpath(sys_get_temp_dir());
foreach (array('active', 'supplied', 'kept') as $name) {
    mkdir($root . '/' . $name, 0700);
}
file_put_contents($root . '/kept/.hidden', 'unchanged');
$link = $root . '/stale-link';
symlink($root . '/kept' . ($argv[1] === 'dangling' ? '/absent' : ''), $link);
$initial = getcwd();
try {
    chdir($root . '/active');
    Extract_low_options::_removeTmpFiles($link . ($argv[1] === 'trailing' ? '/' : ''), $root . '/supplied');
    echo json_encode(array(
        'restored' => getcwd() === $root . '/supplied',
        'removed' => !is_link($link),
        'retained' => file_get_contents($root . '/kept/.hidden'),
    ), JSON_THROW_ON_ERROR), "\n";
} finally {
    chdir($initial);
}
