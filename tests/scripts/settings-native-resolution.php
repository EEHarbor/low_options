<?php

namespace Low\Options\FluxCapacitor\Conduit {
    const PHP_INT_SIZE = 1;
    const E_NOTICE = 912;
    const E_WARNING = 913;

    /**
     * Retain an unrelated namespace helper without intercepting native support.
     *
     * @param mixed ...$arguments
     * @return mixed
     */
    function strlen(...$arguments)
    {
        $GLOBALS['owned_support_calls']++;
        return 0;
    }

    /**
     * Retain an unrelated namespace helper without intercepting native support.
     *
     * @param mixed ...$arguments
     * @return mixed
     */
    function strpos(...$arguments)
    {
        $GLOBALS['owned_support_calls']++;
        return false;
    }

    /**
     * Retain an unrelated namespace helper without intercepting native support.
     *
     * @param mixed ...$arguments
     * @return mixed
     */
    function preg_match(...$arguments)
    {
        $GLOBALS['owned_support_calls']++;
        return false;
    }

    /**
     * Retain an unrelated namespace helper without intercepting native support.
     *
     * @param mixed ...$arguments
     * @return mixed
     */
    function in_array(...$arguments)
    {
        $GLOBALS['owned_support_calls']++;
        return false;
    }

    /**
     * Retain an unrelated namespace helper without intercepting native support.
     *
     * @param mixed ...$arguments
     * @return mixed
     */
    function substr(...$arguments)
    {
        $GLOBALS['owned_support_calls']++;
        return '';
    }

    /**
     * Retain an unrelated namespace helper without intercepting native support.
     *
     * @param mixed ...$arguments
     * @return mixed
     */
    function strspn(...$arguments)
    {
        $GLOBALS['owned_support_calls']++;
        return 0;
    }

    /**
     * Retain an unrelated namespace helper without intercepting native support.
     *
     * @param mixed ...$arguments
     * @return mixed
     */
    function chr(...$arguments)
    {
        $GLOBALS['owned_support_calls']++;
        return '';
    }

    /**
     * Retain an unrelated namespace helper without intercepting native support.
     *
     * @param mixed ...$arguments
     * @return mixed
     */
    function hexdec(...$arguments)
    {
        $GLOBALS['owned_support_calls']++;
        return 0;
    }

    /**
     * Retain an unrelated namespace helper without intercepting native support.
     *
     * @param mixed ...$arguments
     * @return mixed
     */
    function ltrim(...$arguments)
    {
        $GLOBALS['owned_support_calls']++;
        return '';
    }

    /**
     * Retain an unrelated namespace helper without intercepting native support.
     *
     * @param mixed ...$arguments
     * @return mixed
     */
    function strcmp(...$arguments)
    {
        $GLOBALS['owned_support_calls']++;
        return 0;
    }

    /**
     * Retain an unrelated namespace helper without intercepting native support.
     *
     * @param mixed ...$arguments
     * @return mixed
     */
    function set_error_handler(...$arguments)
    {
        $GLOBALS['owned_support_calls']++;
        return null;
    }

    /**
     * Retain an unrelated namespace helper without intercepting native support.
     *
     * @param mixed ...$arguments
     * @return mixed
     */
    function restore_error_handler(...$arguments)
    {
        $GLOBALS['owned_support_calls']++;
        return false;
    }

    /**
     * Retain an unrelated namespace helper without intercepting native support.
     *
     * @param mixed ...$arguments
     * @return mixed
     */
    function call_user_func_array(...$arguments)
    {
        $GLOBALS['owned_support_calls']++;
        return null;
    }

    /**
     * Retain an unrelated namespace helper without intercepting native support.
     *
     * @param mixed ...$arguments
     * @return mixed
     */
    function func_get_args(...$arguments)
    {
        $GLOBALS['owned_support_calls']++;
        return array();
    }


}

namespace {
require __DIR__ . '/settings-native-database.php';
$GLOBALS['owned_math_calls'] = 0;
$GLOBALS['owned_support_calls'] = 0;
$app = (object) array('db' => new SettingsContractDatabase());

/**
 * Resolve the one owned native Active Record settings collaborator.
 *
 * @return object
 */
function ee() { return $GLOBALS['app']; }
$archive = dirname(__DIR__, 2) . '/low_options/low_options.phar';
require 'phar://' . $archive . '/FluxCapacitor/Conduit/SerializedValue.php';
require 'phar://' . $archive . '/FluxCapacitor/Conduit/GlobalFieldSettings.php';
$settings = (new ReflectionClass(\Low\Options\FluxCapacitor\Conduit\GlobalFieldSettings::class))->newInstanceWithoutConstructor();
$settings->fieldtype_name = 'low_options';
$app->db->blob = base64_encode(serialize(array(1000 => 'native', 'keep' => 'retained')));
$read = $settings->getSetting(1000.5);
$saved = $settings->updateSetting(1000.5, 'changed');
$wire = base64_decode($app->db->written);
$app->db->blob = base64_encode('S:3:"abc";');
$legacy = $settings->getSettings();
echo json_encode(array('read' => $read, 'saved' => $saved, 'wire' => $wire, 'legacy' => $legacy, 'support_calls' => $GLOBALS['owned_support_calls'],
    'helper_calls' => $GLOBALS['owned_math_calls']), JSON_THROW_ON_ERROR), "\n";
}
