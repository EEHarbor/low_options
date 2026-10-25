<?php

final class LifetimeDatabase
{
    public $rows = 1;
    public $rowAction;
    public $countAction;

    /**
     * Retain the selected field.
     *
     * @param string $field
     * @return self
     */
    public function select($field) { return $this; }
    /**
     * Retain the native query filter.
     *
     * @param string $field
     * @param mixed $value
     * @return self
     */
    public function where($field, $value) { return $this; }
    /**
     * Return the owned stored query.
     *
     * @param string $table
     * @return self
     */
    public function get($table) { return $this; }
    /**
     * Allow the real query-count callback before native expression evaluation.
     *
     * @return int
     */
    public function num_rows()
    {
        if ($this->countAction) {
            $action = $this->countAction;
            $this->countAction = null;
            $action();
        }
        return $this->rows;
    }
    /**
     * Run the row callback after native callees have been resolved.
     *
     * @param string $field
     * @return string
     * @throws RuntimeException
     */
    public function row($field)
    {
        if ($this->rowAction) {
            $action = $this->rowAction;
            $this->rowAction = null;
            $action();
        }
        return base64_encode('i:7;');
    }
}

/**
 * Return the owned native EE collaborators.
 *
 * @return object
 */
function ee() { return $GLOBALS['lifetime_app']; }
/**
 * Preserve file-helper callbacks before the raw cache parser is invoked.
 *
 * @param string $path
 * @return string|false
 */
function read_file($path)
{
    if (isset($GLOBALS['lifetime_read_action'])) {
        $action = $GLOBALS['lifetime_read_action'];
        unset($GLOBALS['lifetime_read_action']);
        $action();
    }
    if (!empty($GLOBALS['lifetime_read_missing'])) {
        $GLOBALS['lifetime_read_missing'] = false;
        return false;
    }
    return 'i:7;';
}
/**
 * Register legal hooks at the actual callback boundary being observed.
 *
 * @param string $which
 * @return void
 */
function lifetimeHooks($which)
{
    $ns = $GLOBALS['lifetime_namespace'];
    if (($which === 'both' || $which === 'outer') && !function_exists($ns . '\\unserialize')) {
        eval('namespace ' . $ns . '; function unserialize($value) { $GLOBALS["outer_calls"]++; return "outer-hook"; }');
    }
    if (($which === 'both' || $which === 'inner') && !function_exists($ns . '\\base64_decode')) {
        eval('namespace ' . $ns . '; function base64_decode($value) { $GLOBALS["inner_calls"]++; return \\base64_decode($value); }');
    }
}

$archive = $argv[1];
$namespace = $argv[2] . '\\FluxCapacitor\\Conduit';
$GLOBALS['lifetime_namespace'] = $namespace;
$GLOBALS['outer_calls'] = 0;
$GLOBALS['inner_calls'] = 0;
$scenario = $argv[3];
$route = $argv[4];
$db = new LifetimeDatabase();
$GLOBALS['lifetime_app'] = (object) array('db' => $db, 'load' => new class {
    /**
     * Preserve native helper loading.
     *
     * @param string $name
     * @return void
     */
    public function helper($name) {}
    /**
     * Preserve native logger loading.
     *
     * @param string $name
     * @return void
     */
    public function library($name) {}
}, 'logger' => new class {
    /**
     * Retain the native quiet cache-miss logging boundary.
     *
     * @param string $message
     * @return void
     */
    public function developer($message) {}
});
spl_autoload_register(static function ($class) use ($archive, $argv) {
    $prefix = $argv[2] . '\\';
    if (strpos($class, $prefix) === 0) {
        $path = 'phar://' . $archive . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (file_exists($path)) {
            require $path;
        }
    }
});
$class = $namespace . '\\GlobalFieldSettings';
$cache = $namespace . '\\PersistentCache';
$items = array();
$base = null;
$child = null;
if ($route === 'settings') {
    class_exists($class);
    eval('class LifetimeSettingsChild extends \\' . $class . ' {}');
    $base = (new ReflectionClass($class))->newInstanceWithoutConstructor();
    $base->fieldtype_name = 'owned-lifetime';
    $child = (new ReflectionClass('LifetimeSettingsChild'))->newInstanceWithoutConstructor();
    $child->fieldtype_name = 'owned-lifetime';
    $invoke = static function ($childFirst = false) use ($base, $child) {
        return ($childFirst ? $child : $base)->getSettings();
    };
} else {
    $directory = sys_get_temp_dir() . '/lifetime-cache';
    mkdir($directory . '/low_options', 0700, true);
    define('PATH_CACHE', $directory . '/');
    define('DIR_WRITE_MODE', 0700);
    define('FILE_WRITE_MODE', 0600);
    $counter = 0;
    $invoke = static function ($unused = false) use ($cache, &$counter) {
        $counter++;
        return $cache::get('owned-' . $counter);
    };
}

if ($scenario === 'preexisting') {
    lifetimeHooks('both');
}
if ($scenario === 'row' || $scenario === 'row-throws' || $scenario === 'recursive') {
    $db->rowAction = static function () use ($scenario, $invoke, &$items) {
        lifetimeHooks('both');
        if ($scenario === 'row-throws') {
            throw new RuntimeException('owned row callback');
        }
        if ($scenario === 'recursive') {
            $items[] = array('recursive' => $invoke(true));
        }
    };
}
if ($scenario === 'num-rows') {
    $db->countAction = static function () { lifetimeHooks('both'); };
}
if ($scenario === 'inner-registers-outer') {
    eval('namespace ' . $namespace . '; function base64_decode($value) { $GLOBALS["inner_calls"]++; \\lifetimeHooks("outer"); return \\base64_decode($value); }');
}
if ($scenario === 'outer-only-row-inner') {
    lifetimeHooks('outer');
    $db->rowAction = static function () { lifetimeHooks('inner'); };
}
if ($scenario === 'read-registers') {
    $GLOBALS['lifetime_read_action'] = static function () { lifetimeHooks('outer'); };
}
if ($scenario === 'no-row') {
    $db->rows = 0;
}
if ($scenario === 'read-missing') {
    $GLOBALS['lifetime_read_missing'] = true;
}
try {
    $items[] = array('first' => $invoke($scenario === 'child-first'));
} catch (RuntimeException $error) {
    if ($error->getMessage() !== 'owned row callback') {
        throw $error;
    }
    $items[] = array('first' => 'owned-row-throw');
}
if (in_array($scenario, array('between', 'child-first', 'no-row', 'read-missing'), true)) {
    lifetimeHooks('both');
    $db->rows = 1;
}
$items[] = array('second' => $invoke());
if ($route === 'settings') {
    $items[] = array('inherited' => $invoke(true));
}
echo json_encode(array('items' => $items, 'outer_calls' => $GLOBALS['outer_calls'],
    'inner_calls' => $GLOBALS['inner_calls']), JSON_THROW_ON_ERROR), "\n";
