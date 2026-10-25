<?php

final class CacheSiteStream
{
    public $context;
    private $handle;

    /**

     * Resolve a physical path owned by this probe.

     * @param string $path

     * @return string

     */
    private function target($path) { return $GLOBALS['cache_site_root'] . substr($path, strlen('ownedsite://')); }
    /**
     * Observe native directory state.
     * @param string $path
     * @param int $flags
     * @return array|false
     */
    public function url_stat($path, $flags)
    {
        if ($GLOBALS['cache_site_case'] === 'parent' && rtrim($path, '/') === 'ownedsite://root') {
            return false;
        }
        $path = $this->target($path);
        return file_exists($path) ? stat($path) : false;
    }
    /**
     * Register a hook during the first native creation.
     * @param string $path
     * @param int $mode
     * @param int $options
     * @return bool
     */
    public function mkdir($path, $mode, $options)
    {
        $GLOBALS['cache_site_native'][] = array($path, error_reporting());
        if ($GLOBALS['cache_site_case'] === 'late') {
            cacheSiteInstallHook();
        }
        return mkdir($this->target($path), $mode);
    }
    /**
     * Apply native directory permissions.
     * @param string $path
     * @param int $option
     * @param mixed $value
     * @return bool
     */
    public function stream_metadata($path, $option, $value) { return $option === STREAM_META_ACCESS ? chmod($this->target($path), $value) : false; }
    /**
     * Open the actual EE helper's cache file.
     * @param string $path
     * @param string $mode
     * @param int $options
     * @param mixed $opened
     * @return bool
     */
    public function stream_open($path, $mode, $options, &$opened) { $this->handle = fopen($this->target($path), $mode); return $this->handle !== false; }
    /**
     * Lock the owned file.
     * @param int $operation
     * @return bool
     */
    public function stream_lock($operation) { return flock($this->handle, $operation); }
    /**
     * Write the original serialized value.
     * @param string $data
     * @return int|false
     */
    public function stream_write($data) { return fwrite($this->handle, $data); }
    /**
     * Release the owned file.
     * @return void
     */
    public function stream_close() { fclose($this->handle); }
    /**
     * Return file metadata.
     * @return array|false
     */
    public function stream_stat() { return fstat($this->handle); }
}

/**

 * Register a legal namespace hook without diagnostic allowances.

 * @return void

 */
function cacheSiteInstallHook()
{
    $namespace = $GLOBALS['cache_site_namespace'] . '\\FluxCapacitor\\Conduit';
    if (!function_exists($namespace . '\\mkdir')) {
        eval('namespace ' . $namespace . '; function mkdir($path, $mode) { $GLOBALS["cache_site_hooks"][] = array($path, $mode, \\error_reporting()); return false; }');
    }
}

/**

 * Resolve the real EE helper dependencies.

 * @return object

 */
function ee() { return $GLOBALS['cache_site_app']; }

$archive = $argv[1];
$GLOBALS['cache_site_namespace'] = $argv[2];
$GLOBALS['cache_site_case'] = $argv[3];
$GLOBALS['cache_site_native'] = array();
$GLOBALS['cache_site_hooks'] = array();
$GLOBALS['cache_site_logs'] = array();
$GLOBALS['cache_site_root'] = sys_get_temp_dir() . '/mkdir-sites-' . bin2hex(random_bytes(8)) . '/';
mkdir($GLOBALS['cache_site_root'] . 'root', 0700, true);
stream_wrapper_register('ownedsite', CacheSiteStream::class);
define('BASEPATH', __DIR__);
define('PATH_CACHE', 'ownedsite://root/cache/');
define('DIR_WRITE_MODE', 0700);
define('FILE_WRITE_MODE', 0600);
define('FOPEN_WRITE_CREATE_DESTRUCTIVE', 'wb');
require $argv[4];
$GLOBALS['cache_site_app'] = (object) array('load' => new class {
    /**
     * Retain the helper boundary.
     * @param string $name
     * @return void
     */
    public function helper($name) {}
    /**
     * Retain the logger boundary.
     * @param string $name
     * @return void
     */
    public function library($name) {}
}, 'logger' => new class {
    /**
     * Record ordinary native failure reporting.
     * @param string $message
     * @return void
     */
    public function developer($message) { $GLOBALS['cache_site_logs'][] = $message; }
});
spl_autoload_register(static function ($class) use ($archive) {
    $prefix = $GLOBALS['cache_site_namespace'] . '\\';
    if (strpos($class, $prefix) === 0) {
        $path = 'phar://' . $archive . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (file_exists($path)) { require $path; }
    }
});
$cache = $GLOBALS['cache_site_namespace'] . '\\FluxCapacitor\\Conduit\\PersistentCache';
$module = $argv[5];
$physical = $GLOBALS['cache_site_root'] . 'root/cache/';
$case = $GLOBALS['cache_site_case'];
if ($case === 'unused') { mkdir($physical, 0700); }
if ($case === 'snapshot') {
    $tool = $argv[6];
    require 'phar://' . $tool . '/sebastian-recursion-context/Context.php';
    require 'phar://' . $tool . '/sebastian-object-reflector/ObjectReflector.php';
    foreach (array('ExcludeList', 'Snapshot', 'Restorer') as $part) { require 'phar://' . $tool . '/sebastian-global-state/' . $part . '.php'; }
    class_exists($cache);
    $snapshot = new PHPUnitPHAR\SebastianBergmann\GlobalState\Snapshot(null, false, true, false, false, true, false, false, false, false);
}
$results = array();
try {
    $results[] = $cache::set('entry', 'stored');
    if ($case !== 'late' && $case !== 'parent') {
        unlink($physical . $module . '/' . sha1('entry'));
        rmdir($physical . $module);
        rmdir($physical);
        cacheSiteInstallHook();
        if ($case === 'snapshot') {
            (new PHPUnitPHAR\SebastianBergmann\GlobalState\Restorer())->restoreStaticAttributes($snapshot);
        }
        $results[] = $cache::set('entry', 'stored');
    }
    echo json_encode(array('results' => $results, 'native' => $GLOBALS['cache_site_native'], 'hooks' => $GLOBALS['cache_site_hooks'], 'logs' => $GLOBALS['cache_site_logs'], 'stored' => $case === 'parent' && is_file($physical . $module . '/' . sha1('entry')) ? file_get_contents($physical . $module . '/' . sha1('entry')) : false), JSON_THROW_ON_ERROR), "\n";
} finally {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($GLOBALS['cache_site_root'], FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) { $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname()); }
    rmdir($GLOBALS['cache_site_root']);
}
