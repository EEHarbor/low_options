<?php

final class StaticRestoreQuery
{
    /**
     * Retain the selected field.
     *
     * @param string $field
     * @return self
     */
    public function select($field) { return $this; }

    /**
     * Retain the fieldtype predicate.
     *
     * @param string $field
     * @param mixed $value
     * @return self
     */
    public function where($field, $value) { return $this; }

    /**
     * Supply one owned settings query.
     *
     * @param string $table
     * @return self
     */
    public function get($table) { return $this; }

    /**
     * Supply one stored fieldtype.
     *
     * @return int
     */
    public function num_rows() { return 1; }

    /**
     * Supply canonical serialized bytes.
     *
     * @param string $field
     * @return string
     */
    public function row($field) { return base64_encode('i:7;'); }
}

/**
 * Resolve the owned query container.
 *
 * @return object
 */
function ee() { return $GLOBALS['static_restore_app']; }

/**
 * Supply a valid persisted value through the original file-reader boundary.
 *
 * @param string $path
 * @return string
 */
function read_file($path) { return 'i:7;'; }

$archive = $argv[1];
$route = $argv[2];
$when = $argv[3];
$root = dirname(__DIR__, 2);
$tool = $root . '/tests/.phpunit/phpunit-9.phar';
require 'phar://' . $tool . '/sebastian-recursion-context/Context.php';
require 'phar://' . $tool . '/sebastian-object-reflector/ObjectReflector.php';
foreach (array('ExcludeList', 'Snapshot', 'Restorer') as $component) {
    require 'phar://' . $tool . '/sebastian-global-state/' . $component . '.php';
}
$snapshotState = static function () {
    return new PHPUnitPHAR\SebastianBergmann\GlobalState\Snapshot(null, false, true, false, false, true, false, false, false, false);
};
if ($when === 'before-source') {
    $snapshot = $snapshotState();
}
$directory = sys_get_temp_dir() . '/settings-static-restore';
mkdir($directory . '/low_options', 0700, true);
define('PATH_CACHE', $directory . '/');
define('DIR_WRITE_MODE', 0700);
define('FILE_WRITE_MODE', 0600);
spl_autoload_register(static function ($class) use ($archive) {
    $prefix = 'Low\\Options\\';
    if (strpos($class, $prefix) === 0) {
        $path = 'phar://' . $archive . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (file_exists($path)) {
            require $path;
        }
    }
});
$GLOBALS['static_restore_app'] = (object) array('db' => new StaticRestoreQuery(), 'load' => new class {
    /**
     * Retain the original file-helper loading boundary.
     *
     * @param string $name
     * @return void
     */
    public function helper($name) {}
});
$settings = (new ReflectionClass(Low\Options\FluxCapacitor\Conduit\GlobalFieldSettings::class))->newInstanceWithoutConstructor();
$settings->fieldtype_name = 'owned-static-restore';
class_exists(Low\Options\FluxCapacitor\Conduit\PersistentCache::class);
$read = static function () use ($settings, $route) {
    if ($route === 'settings') {
        return $settings->getSettings();
    }
    Low\Options\FluxCapacitor\Conduit\StaticCache::clear();
    return Low\Options\FluxCapacitor\Conduit\PersistentCache::get('owned-static-restore');
};
if ($when === 'after-other-reader') {
    $settings->getSettings();
}
if ($when !== 'before-source') {
    $snapshot = $snapshotState();
}
$values = array($read());
eval('namespace Low\\Options\\FluxCapacitor\\Conduit; function unserialize($value) { return "late-parser"; } function base64_decode($value) { return \\base64_decode($value); }');
(new PHPUnitPHAR\SebastianBergmann\GlobalState\Restorer())->restoreStaticAttributes($snapshot);
$values[] = $read();
$values[] = $read();
echo json_encode($values, JSON_THROW_ON_ERROR), "\n";
