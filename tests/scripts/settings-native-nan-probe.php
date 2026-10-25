<?php

namespace Low\Options\FluxCapacitor\Conduit {
    /**
     * Consume the unchanged native decoded bytes through an existing parser hook.
     *
     * @param string $wire
     * @return array
     */
    function unserialize($wire)
    {
        return array('decoded' => base64_encode($wire), 'reporting' => error_reporting());
    }
}

namespace {
    final class NativeNanCarrierQuery
    {
        /**
         * Retain the original selected field.
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
         * Supply the owned fieldtype row.
         *
         * @param string $table
         * @return self
         */
        public function get($table) { return $this; }

        /**
         * Report one stored carrier.
         *
         * @return int
         */
        public function num_rows() { return 1; }

        /**
         * Supply the original quiet PHP74 numeric carrier.
         *
         * @param string $field
         * @return float
         */
        public function row($field) { return NAN; }
    }

    /**
     * Resolve the original Active Record collaborator.
     *
     * @return object
     */
    function ee() { return $GLOBALS['nan_carrier_app']; }

    $GLOBALS['nan_carrier_app'] = (object) array('db' => new NativeNanCarrierQuery());
    require 'phar://' . $argv[1] . '/FluxCapacitor/Conduit/GlobalFieldSettings.php';
    $settings = (new ReflectionClass(Low\Options\FluxCapacitor\Conduit\GlobalFieldSettings::class))->newInstanceWithoutConstructor();
    $settings->fieldtype_name = 'owned-nan';
    echo json_encode($settings->getSettings(), JSON_THROW_ON_ERROR), "\n";
}
