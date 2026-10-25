<?php

/**
 * Observe the original raw key through a legal caller-local serialization hook.
 *
 * @param mixed $value
 * @return string
 */
function ownedKeySerialize($value)
{
    $frames = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3);
    foreach ($frames as $frame) {
        if ($frame['function'] === 'updateSetting') {
            $key = $frame['args'][0];
            $GLOBALS['raw_key_events'][] = array(gettype($key), $key);
        }
    }
    return serialize($value);
}

final class RawKeyDatabase
{
    /**
     * Retain query selection.
     * @param string $name
     * @return self
     */
    public function select($name) { return $this; }
    /**
     * Retain fieldtype filtering.
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function where($name, $value) { return $this; }
    /**
     * Return the owned row.
     * @param string $table
     * @return self
     */
    public function get($table) { return $this; }
    /**
     * Supply one stored row.
     * @return int
     */
    public function num_rows() { return 1; }
    /**
     * Supply canonical array settings.
     * @param string $field
     * @return string
     */
    public function row($field) { return base64_encode(serialize(array('keep' => 'native'))); }
    /**
     * Retain the native persistence boundary.
     * @param string $table
     * @param array $data
     * @return bool
     */
    public function update($table, $data) { return true; }
}

/**

 * Resolve the owned query boundary.

 * @return object

 */
function ee() { return $GLOBALS['raw_key_app']; }

$archive = $argv[1];
$prefix = $argv[2];
$GLOBALS['raw_key_events'] = array();
$GLOBALS['raw_key_app'] = (object) array('db' => new RawKeyDatabase());
eval('namespace ' . $prefix . '\\FluxCapacitor\\Conduit; function serialize($value) { return \\ownedKeySerialize($value); }');
spl_autoload_register(static function ($class) use ($archive, $prefix) {
    if (strpos($class, $prefix . '\\') === 0) {
        $path = 'phar://' . $archive . '/' . str_replace('\\', '/', substr($class, strlen($prefix) + 1)) . '.php';
        if (file_exists($path)) { require $path; }
    }
});
$class = $prefix . '\\FluxCapacitor\\Conduit\\GlobalFieldSettings';
$settings = (new ReflectionClass($class))->newInstanceWithoutConstructor();
$settings->fieldtype_name = 'owned';
$settings->updateSetting($argv[3] === 'fraction' ? 1.9 : null, 'value');
echo json_encode($GLOBALS['raw_key_events'], JSON_THROW_ON_ERROR), "\n";
