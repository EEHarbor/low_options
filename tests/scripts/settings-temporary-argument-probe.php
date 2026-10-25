<?php
final class CarrierHydratedValue
{
    public $ready = false;
    /**
     * Observe whether native temporary release precedes hydration.
     *
     * @return void
     */
    public function __wakeup()
    {
        $this->ready = $GLOBALS['carrier_destroyed'];
        $GLOBALS['carrier_events'][] = array('hydrate', $this->ready, error_reporting());
    }
}
final class NativeTemporaryCarrier
{
    /**
     * Supply native bytes or the exact owned conversion exception.
     *
     * @return string
     * @throws RuntimeException
     */
    public function __toString()
    {
        $GLOBALS['carrier_events'][] = array('convert', error_reporting());
        if ($GLOBALS['carrier_failure'] === 'conversion' || $GLOBALS['carrier_failure'] === 'both') {
            throw $GLOBALS['carrier_conversion_error'];
        }
        return $GLOBALS['carrier_route'] === 'raw' ? base64_encode($GLOBALS['carrier_wire']) : $GLOBALS['carrier_wire'];
    }
    /**
     * Record argument release and retain the exact owned destructor failure.
     *
     * @return void
     * @throws RuntimeException
     */
    public function __destruct()
    {
        $GLOBALS['carrier_destroyed'] = true;
        $GLOBALS['carrier_events'][] = array('destroy', error_reporting());
        if ($GLOBALS['carrier_failure'] === 'destructor' || $GLOBALS['carrier_failure'] === 'both') {
            throw $GLOBALS['carrier_destructor_error'];
        }
    }
}
final class CarrierQuery
{
    /**
     * Retain the Active Record selection boundary.
     *
     * @param string $field
     * @return self
     */
    public function select($field) { return $this; }
    /**
     * Retain the fieldtype-name predicate.
     *
     * @param string $field
     * @param mixed $value
     * @return self
     */
    public function where($field, $value) { return $this; }
    /**
     * Return the owned query object.
     *
     * @param string $table
     * @return object
     */
    public function get($table) { return $this; }
    /**
     * Supply one canonical fieldtype row.
     *
     * @return int
     */
    public function num_rows() { return 1; }
    /**
     * Supply a temporary native carrier without retaining another reference.
     *
     * @param string $field
     * @return mixed
     */
    public function row($field) { return $GLOBALS['carrier_route'] === 'raw' ? new NativeTemporaryCarrier() : 'original-row'; }
    /**
     * Record query cleanup after native argument unwinding.
     *
     * @return void
     */
    public function __destruct() { $GLOBALS['carrier_events'][] = array('query-destroy', error_reporting()); }
}
final class CarrierDatabase
{
    /**
     * Retain the Active Record selection boundary.
     *
     * @param string $field
     * @return self
     */
    public function select($field) { return $this; }
    /**
     * Retain the fieldtype-name predicate.
     *
     * @param string $field
     * @param mixed $value
     * @return self
     */
    public function where($field, $value) { return $this; }
    /**
     * Return the owned query object.
     *
     * @param string $table
     * @return object
     */
    public function get($table) { return new CarrierQuery(); }
}
/**
 * Resolve the owned Active Record collaborator.
 *
 * @return object
 */
function ee() { return $GLOBALS['carrier_app']; }
$archive = $argv[1];
$namespace = $argv[2] . '\\FluxCapacitor\\Conduit';
$GLOBALS['carrier_route'] = $argv[3];
$GLOBALS['carrier_failure'] = $argv[4];
$GLOBALS['carrier_conversion_error'] = new RuntimeException('owned conversion failure');
$GLOBALS['carrier_destructor_error'] = new RuntimeException('owned destructor failure');
$GLOBALS['carrier_events'] = array();
$GLOBALS['carrier_destroyed'] = false;
$GLOBALS['carrier_wire'] = serialize(new CarrierHydratedValue());
$GLOBALS['carrier_app'] = (object) array('db' => new CarrierDatabase());
if ($argv[3] === 'decoded') {
    eval('namespace ' . $namespace . '; function base64_decode($value) { return new \\NativeTemporaryCarrier(); }');
}
spl_autoload_register(static function ($class) use ($archive, $argv) {
    $prefix = $argv[2] . '\\';
    if (strpos($class, $prefix) === 0) {
        $path = 'phar://' . $archive . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (file_exists($path)) { require $path; }
    }
});
$class = $namespace . '\\GlobalFieldSettings';
$settings = (new ReflectionClass($class))->newInstanceWithoutConstructor();
$settings->fieldtype_name = 'owned-carrier';
try {
    $value = $settings->getSettings();
    $outcome = array('ready' => $value->ready);
} catch (Throwable $error) {
    $outcome = array('exception' => $error === $GLOBALS['carrier_conversion_error'] ? 'conversion' : ($error === $GLOBALS['carrier_destructor_error'] ? 'destructor' : get_class($error)));
}
echo json_encode(array('outcome' => $outcome, 'destroyed' => $GLOBALS['carrier_destroyed'],
    'events' => $GLOBALS['carrier_events']), JSON_THROW_ON_ERROR), "\n";
