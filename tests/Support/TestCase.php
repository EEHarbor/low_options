<?php

namespace LowOptionsTests;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected $ee;
    private $requestGlobals;
    private $includePath;
    private $autoloaders;

    /**
     * Create an isolated EE request and strict diagnostic handler.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->requestGlobals = array(
            '_GET' => $_GET, '_POST' => $_POST, '_REQUEST' => $_REQUEST,
            '_SERVER' => $_SERVER, '_COOKIE' => $_COOKIE, '_FILES' => $_FILES,
        );
        $this->includePath = get_include_path();
        $this->autoloaders = spl_autoload_functions() ?: array();
        $this->ee = new Environment();
        $GLOBALS['low_options_ee'] = $this->ee;
        set_error_handler(function ($severity, $message, $file, $line) {
            // Source diagnostics remain failures even if production used @.
            if (strpos($file, LOW_OPTIONS_ROOT . '/low_options/') !== false) {
                throw new \ErrorException($message, 0, $severity, $file, $line);
            }
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
    }

    /**
     * Restore diagnostics and release request-local state.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        restore_error_handler();
        foreach ($this->requestGlobals as $key => $value) {
            $GLOBALS[$key] = $value;
        }
        set_include_path($this->includePath);
        foreach (spl_autoload_functions() ?: array() as $autoloader) {
            if (!in_array($autoloader, $this->autoloaders, true)) {
                spl_autoload_unregister($autoloader);
            }
        }
        unset($GLOBALS['low_options_ee']);
        if (class_exists('Low\\Options\\FluxCapacitor\\Conduit\\StaticCache', false)) {
            \Low\Options\FluxCapacitor\Conduit\StaticCache::clear();
        }
    }

    /**
     * Invoke a source method without changing visibility in production.
     *
     * @param object|string $object
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    protected function invoke($object, $method, array $arguments = array())
    {
        $reflection = new \ReflectionMethod($object, $method);
        if (PHP_VERSION_ID < 80100) {
            $reflection->setAccessible(true);
        }
        return $reflection->invokeArgs(is_object($object) ? $object : null, $arguments);
    }

}
