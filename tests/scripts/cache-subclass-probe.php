<?php

define('PATH_CACHE', sys_get_temp_dir() . '/');
require 'phar://' . $argv[1] . '/FluxCapacitor/Conduit/PersistentCache.php';

$method = $argv[2];
$visibility = $argv[3];
$static = $argv[4] === 'static' ? 'static ' : '';
if (!in_array($method, array('createDirectory', 'isWritableDirectory'), true)
    || !in_array($visibility, array('public', 'protected', 'private'), true)) {
    throw new InvalidArgumentException('Unknown owned subclass case.');
}
eval('class ExistingCacheChild extends \\Low\\Options\\FluxCapacitor\\Conduit\\PersistentCache { '
    . $visibility . ' ' . $static . 'function ' . $method . '($value) { return $value; } }');
$reflection = new ReflectionClass(ExistingCacheChild::class);
$child = $reflection->newInstanceWithoutConstructor();
$childMethod = $reflection->getMethod($method);
if (PHP_VERSION_ID < 80100) {
    $childMethod->setAccessible(true);
}
echo json_encode(array('value' => $childMethod->invoke($child, 'existing child method')), JSON_THROW_ON_ERROR), "\n";
