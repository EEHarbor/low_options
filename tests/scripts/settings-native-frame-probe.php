<?php

namespace Low\Options\FluxCapacitor\Conduit {
    /**
     * Return the temporary native parser operand from the original host hook.
     *
     * @param mixed $carrier
     * @return \OwnedNativeFrameCarrier
     */
    function base64_decode($carrier) { return new \OwnedNativeFrameCarrier(); }
}

namespace {
    final class OwnedNativeFrameCarrier
    {
        /**
         * Return canonical bytes according to the actual native conversion frame.
         *
         * @return string
         */
        public function __toString()
        {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $frame = $trace[1]['function'] ?? '';
            $GLOBALS['frame_events'][] = array('frame' => $frame, 'reporting' => error_reporting());
            return serialize(array('kept' => $frame === 'unserialize' ? 'native' : 'changed'));
        }
    }

    final class OwnedNativeFrameDB
    {
        /**
         * Retain the selected field.
         *
         * @param string $key
         * @return self
         */
        public function select($key) { return $this; }

        /**
         * Retain the fieldtype predicate.
         *
         * @param string $key
         * @param mixed $value
         * @return self
         */
        public function where($key, $value) { return $this; }

        /**
         * Supply the owned settings query.
         *
         * @param string $table
         * @return self
         */
        public function get($table) { return $this; }

        /**
         * Supply one stored row.
         *
         * @return int
         */
        public function num_rows() { return 1; }

        /**
         * Supply the untouched host-hook carrier.
         *
         * @param string $key
         * @return string
         */
        public function row($key) { return 'untouched-row'; }
    }

    /**
     * Resolve the original Active Record collaborator.
     *
     * @return object
     */
    function ee() { return $GLOBALS['frame_ee']; }

    $archive = $argv[1];
    $GLOBALS['frame_events'] = array();
    $GLOBALS['frame_ee'] = (object) array('db' => new OwnedNativeFrameDB());
    spl_autoload_register(static function ($class) use ($archive) {
        $prefix = 'Low\\Options\\';
        if (strpos($class, $prefix) === 0) {
            $path = 'phar://' . $archive . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (file_exists($path)) {
                require $path;
            }
        }
    });
    $settings = (new ReflectionClass(Low\Options\FluxCapacitor\Conduit\GlobalFieldSettings::class))->newInstanceWithoutConstructor();
    $settings->fieldtype_name = 'owned-native-frame';
    echo json_encode(array('value' => $settings->getSettings(), 'events' => $GLOBALS['frame_events']), JSON_THROW_ON_ERROR), "\n";
}
