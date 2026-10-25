<?php

namespace Low\Options\FluxCapacitor\Conduit {
    /**
     * Return a native accepted decoded carrier without changing the raw row.
     *
     * @param mixed $carrier
     * @return \DecodedNativeWire
     */
    function base64_decode($carrier)
    {
        $GLOBALS['owned_carrier_same'] = $carrier === 'original-carrier';
        return new \DecodedNativeWire();
    }
}

namespace {
    require __DIR__ . '/settings-native-database.php';

    final class DecodedNativeWire
    {
        public static $calls = 0;

        /**
         * Retain the single native conversion or the identical callback exception.
         *
         * @return string
         * @throws \RuntimeException
         */
        public function __toString()
        {
            self::$calls++;
            if ($GLOBALS['owned_kind'] === 'throw') {
                throw $GLOBALS['owned_error'];
            }
            return $GLOBALS['owned_decoded_wire'];
        }
    }

    /**
     * Resolve the owned query and native file-helper collaborators.
     *
     * @return object
     */
    function ee() { return $GLOBALS['app']; }

    /**
     * Retain the original read_file hook's native Stringable result.
     *
     * @param string $path
     * @return DecodedNativeWire
     */
    function read_file($path) { return new DecodedNativeWire(); }

    $GLOBALS['owned_kind'] = $argv[2];
    $GLOBALS['owned_error'] = new RuntimeException('owned decoded wire callback');
    $GLOBALS['owned_carrier_same'] = true;
    $wires = array('array' => serialize(array('keep' => 7)), 'legacy-S' => 'S:3:"abc";',
        'prefix' => serialize(array('keep' => 7)) . 'trailing',
        'object' => serialize((object) array('value' => 'native')), 'throw' => 'N;');
    $GLOBALS['owned_decoded_wire'] = $wires[$argv[2]];
    $GLOBALS['app'] = (object) array('db' => new SettingsContractDatabase());
    $GLOBALS['app']->db->blob = 'original-carrier';
    $archive = dirname(__DIR__, 2) . '/low_options/low_options.phar';
    require 'phar://' . $archive . '/FluxCapacitor/Conduit/SerializedValue.php';
    require 'phar://' . $archive . '/FluxCapacitor/Conduit/GlobalFieldSettings.php';
    $same_exception = false;
    $wire = null;
    try {
        if ($argv[1] === 'settings') {
            $settings = (new ReflectionClass(\Low\Options\FluxCapacitor\Conduit\GlobalFieldSettings::class))->newInstanceWithoutConstructor();
            $settings->fieldtype_name = 'low_options';
            $value = $settings->getSettings();
        } else {
            $cache = sys_get_temp_dir() . '/decoded-stringable-cache/';
            mkdir($cache . 'low_options', 0700, true);
            define('PATH_CACHE', $cache);
            $GLOBALS['app']->load = new class {
                /**
                 * Retain native file-helper loading without replacing source bytes.
                 *
                 * @param string $name
                 * @return void
                 */
                public function helper($name) {}
            };
            require 'phar://' . $archive . '/FluxCapacitor/Conduit/StaticCache.php';
            require 'phar://' . $archive . '/FluxCapacitor/Conduit/PersistentCache.php';
            $value = \Low\Options\FluxCapacitor\Conduit\PersistentCache::get('owned-entry');
        }
        $wire = serialize($value);
    } catch (RuntimeException $error) {
        if ($error !== $GLOBALS['owned_error']) {
            throw $error;
        }
        $same_exception = true;
    }
    echo json_encode(array('wire' => $wire, 'calls' => DecodedNativeWire::$calls,
        'same_exception' => $same_exception, 'carrier_same' => $GLOBALS['owned_carrier_same']
            && $GLOBALS['app']->db->blob === 'original-carrier' && $GLOBALS['app']->db->written === null), JSON_THROW_ON_ERROR), "\n";
}
