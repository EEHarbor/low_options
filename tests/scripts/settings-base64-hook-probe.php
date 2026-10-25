<?php

namespace Low\Options\FluxCapacitor\Conduit {
    /**
     * Preserve the original raw database carrier before native decoding.
     *
     * @param mixed $raw
     * @return string
     */
    function base64_decode($raw)
    {
        $GLOBALS['owned_raw_object'] = $raw === $GLOBALS['owned_raw_carrier'];
        return 'S:3:"abc";';
    }

    /**
     * Retain a legal unrelated helper beside the native lexer.
     *
     * @param string $value
     * @return int
     */
    function strlen($value)
    {
        $GLOBALS['owned_support_calls']++;
        return 0;
    }
}

namespace {
    require __DIR__ . '/settings-native-database.php';
    $GLOBALS['owned_raw_carrier'] = (object) array('carrier' => 1);
    $GLOBALS['owned_support_calls'] = 0;
    $app = (object) array('db' => new SettingsContractDatabase());
    $app->db->blob = $GLOBALS['owned_raw_carrier'];

    /**
     * Resolve the owned Active Record query collaborator.
     *
     * @return object
     */
    function ee() { return $GLOBALS['app']; }
    $archive = dirname(__DIR__, 2) . '/low_options/low_options.phar';
    require 'phar://' . $archive . '/FluxCapacitor/Conduit/SerializedValue.php';
    require 'phar://' . $archive . '/FluxCapacitor/Conduit/GlobalFieldSettings.php';
    $settings = (new ReflectionClass(\Low\Options\FluxCapacitor\Conduit\GlobalFieldSettings::class))->newInstanceWithoutConstructor();
    $settings->fieldtype_name = 'low_options';
    echo json_encode(array('value' => $settings->getSettings(), 'raw_object' => $GLOBALS['owned_raw_object'],
        'support_calls' => $GLOBALS['owned_support_calls']), JSON_THROW_ON_ERROR), "\n";
}
