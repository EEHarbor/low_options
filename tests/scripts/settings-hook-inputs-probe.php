<?php
class HookDatabase
{
    public $raw;

    /**
     * Retain the native Active Record query chain.
     *
     * @param mixed $value
     * @return self
     */
    public function select($value) { return $this; }

    /**
     * Retain the fieldtype name predicate.
     *
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function where($name, $value) { return $this; }

    /**
     * Return the owned fieldtypes query result.
     *
     * @param string $name
     * @return self
     */
    public function get($name) { return $this; }

    /**
     * Report the one owned fieldtype record.
     *
     * @return int
     */
    public function num_rows() { return 1; }

    /**
     * Preserve the exact stored carrier passed to the host hook.
     *
     * @param string $name
     * @return mixed
     */
    public function row($name) { return $this->raw; }
}
class OriginalDecodedText
{
    public static $calls = 0;
    public $wire = 'S:3:"abc";';

    /**
     * Detect a conversion that must not precede an existing parser hook.
     *
     * @return string
     */
    public function __toString()
    {
        self::$calls++;
        return $this->wire;
    }
}
$app=(object)array('db'=>new HookDatabase());
/**
 * Resolve the owned native query collaborator.
 *
 * @return object
 */
function ee() { return $GLOBALS['app']; }
$namespace='Low\\Options\\FluxCapacitor\\Conduit';
eval('namespace '.$namespace.'; function base64_decode($raw) { $GLOBALS["hook_inputs"][] = array("base64", gettype($raw), $raw); return $GLOBALS["hook_decoded"]; } function unserialize($wire) { $GLOBALS["hook_inputs"][] = array("unserialize", gettype($wire), $wire); return array("value"=>"original-hook"); } function is_writable($path) { $GLOBALS["owned_writable_paths"][] = $path; return \is_writable($path); }');
$archive=dirname(__DIR__,2).'/low_options/low_options.phar';
if(file_exists('phar://'.$archive.'/FluxCapacitor/Conduit/SerializedValue.php')) require 'phar://'.$archive.'/FluxCapacitor/Conduit/SerializedValue.php';
require 'phar://'.$archive.'/FluxCapacitor/Conduit/GlobalFieldSettings.php';
$settings=(new ReflectionClass($namespace.'\\GlobalFieldSettings'))->newInstanceWithoutConstructor();$settings->fieldtype_name='low_options';$rows=array();
foreach(array('raw-string'=>array('storage','S:3:"abc";'),'raw-null'=>array(null,'S:3:"abc";'),'raw-object'=>array((object)array('carrier'=>1),'S:3:"abc";'),'decoded-array'=>array('storage',array('native'=>'wire')),'decoded-null'=>array('storage',null),'decoded-stringable'=>array('storage',new OriginalDecodedText())) as $name=>$case) {
    $app->db->raw=$case[0];$GLOBALS['hook_decoded']=$case[1];$GLOBALS['hook_inputs']=array();$events=array();
    set_error_handler(static function($severity,$message) use (&$events) { $events[]=array($severity,$message); throw new ErrorException($message,0,$severity); });
    try { $result=array('value'=>$settings->getSettings()); } catch(Throwable $error) { $result=array('exception'=>get_class($error),'message'=>$error->getMessage()); }
    restore_error_handler();$rows[$name]=array('result'=>$result,'inputs'=>$GLOBALS['hook_inputs'],'events'=>$events,'conversions'=>OriginalDecodedText::$calls);
}
$cache = sys_get_temp_dir() . '/original-hook-cache/';
mkdir($cache . 'low_options', 0700, true);
define('PATH_CACHE', $cache);
$app->load = new class {
    /**
     * Retain the native file-helper loading request.
     *
     * @param string $name
     * @return void
     */
    public function helper($name) {}
};

/**
 * Read the actual owned cache file through the original EE helper contract.
 *
 * @param string $path
 * @return string|false
 */
function read_file($path) { return file_get_contents($path); }
require 'phar://' . $archive . '/FluxCapacitor/Conduit/StaticCache.php';
require 'phar://' . $archive . '/FluxCapacitor/Conduit/PersistentCache.php';
file_put_contents($cache . 'low_options/' . sha1('owned-hook'), 'S:3:"abc";');
$GLOBALS['hook_inputs'] = array();
$GLOBALS['owned_writable_paths'] = array();
$cache_class = $namespace . '\\PersistentCache';
$rows['cache'] = array('result' => array('value' => $cache_class::get('owned-hook')), 'inputs' => $GLOBALS['hook_inputs'], 'writable_paths_match' => $GLOBALS['owned_writable_paths'] === array($cache, $cache . 'low_options/'));
echo json_encode($rows,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR),"\n";
