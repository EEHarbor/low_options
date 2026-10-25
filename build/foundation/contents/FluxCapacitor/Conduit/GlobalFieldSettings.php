<?php

namespace @@@ADDON_NAMESPACE@@@\FluxCapacitor\Conduit;

use @@@ADDON_NAMESPACE@@@\FluxCapacitor\FluxCapacitor;

class GlobalFieldSettings
{
    public $flux;
    public $fieldtype_name;
    public function __construct($fieldtype_name=null)
    {
        $this->flux = new FluxCapacitor;
        $this->fieldtype_name = $fieldtype_name ?: $this->flux->shortname;
    }

    /**
     * Read valid native offsets while preserving custom ArrayAccess getters.
     *
     * @param mixed $name
     * @return mixed
     * @throws \Throwable
     */
    public function getSetting($name)
    {
        $ftSettings = $this->getSettings();
        if ($ftSettings instanceof \ArrayAccess) {
            return @$ftSettings[$name];
        }
        if (\is_array($ftSettings)) {
            if (\is_bool($name) || $name === null) {
                $name = $name === null ? '' : (int) $name;
            }
            // Preserve PHP 7.4 integer-width keys without an out-of-range cast.
            if (\is_float($name) && !\is_finite($name)) {
                $name = 0;
            }
            if (\is_float($name)) {
                $width = 2.0 ** (\PHP_INT_SIZE * 8);
                $name = \fmod($name, $width);
                if ($name >= $width / 2) {
                    $name -= $width;
                }
                if ($name < -$width / 2) {
                    $name += $width;
                }
                $name = (int) $name;
            }
        }
        if ((\is_array($ftSettings) || \is_string($ftSettings)) && (\is_int($name) || \is_string($name))) {
            return isset($ftSettings[$name]) ? $ftSettings[$name] : null;
        }
        return null;
    }

    /**
     * Read stored settings with the original native argument and hook lifetime.
     *
     * @return mixed
     * @throws \Throwable
     */
    public function getSettings()
    {
        $ftSettingsQuery = ee()->db->select('settings')
            ->where('name', $this->fieldtype_name)
            ->get('fieldtypes');

        if ((bool) $ftSettingsQuery->num_rows()) {
            // Uninitialized typed flags survive restoration of unrelated static state.
            $callees = new class {
                public static bool $parser;
                public static bool $carrier;
            };
            if (!isset($callees::$parser)) {
                $callees::$parser = \function_exists(__NAMESPACE__ . '\\unserialize');
                $callees::$carrier = \function_exists(__NAMESPACE__ . '\\base64_decode');
            }
            /**
             * Transfer temporary operands to native base64 conversion without retaining them.
             *
             * @param mixed $carrier Original stored row value.
             * @return string|false
             * @throws \Throwable When a native conversion callback fails.
             */
            $nativeCarrier = static function ($carrier) {
                if ($carrier === null || (\is_float($carrier) && \is_nan($carrier))) {
                    $carrier = $carrier === null ? '' : 'NAN';
                }
                $carrier = array($carrier);
                return \base64_decode(\array_pop($carrier));
            };
            $parser = $callees::$parser ? __NAMESPACE__ . '\\unserialize' : array(SerializedValue::class, 'decode');
            $carrier = $callees::$carrier ? __NAMESPACE__ . '\\base64_decode' : $nativeCarrier;
            $ftSettings = @$parser(@$carrier($ftSettingsQuery->row('settings')));
        } else {
            return null;
        }

        // PHP 7.4 retained NAN without PHP 8.5's boolean-coercion diagnostic.
        if (\is_float($ftSettings) && \is_nan($ftSettings)) {
            return $ftSettings;
        }
        return $ftSettings ?: array();
    }

    /**
     * Write one setting while preserving native array keys and string offsets.
     *
     * @param mixed $key
     * @param mixed $value
     * @return bool
     * @throws \Throwable
     */
    public function updateSetting($key, $value)
    {
        $ftSettings = $this->getSettings();
        if (is_null($ftSettings)) {
            return false;
        }
        $offset = $key;
        // Preserve PHP 7.4 integer-width keys without an out-of-range cast.
        if (\is_array($ftSettings) && \is_float($offset) && !\is_finite($offset)) {
            $offset = 0;
        }
        if (\is_array($ftSettings) && \is_float($offset)) {
            $width = 2.0 ** (\PHP_INT_SIZE * 8);
            $offset = \fmod($offset, $width);
            if ($offset >= $width / 2) {
                $offset -= $width;
            }
            if ($offset < -$width / 2) {
                $offset += $width;
            }
            $offset = (int) $offset;
        }
        if (\is_array($ftSettings) && $offset === null) {
            $offset = '';
        }
        $stringOffset = null;
        if (\is_string($ftSettings) && (\is_int($offset) || (\is_string($offset) && \preg_match('/^[\x09-\x0d ]*[+-]?[0-9]+$/D', $offset)))) {
            $stringOffset = $offset + 0;
        }
        // Keep the original parameter alive until the database update finishes.
        $replacement = $value;
        if (\is_int($stringOffset) && $stringOffset >= -\strlen($ftSettings) && $stringOffset < \PHP_INT_MAX
            && (\is_scalar($value) || \is_resource($value) || \gettype($value) === 'resource (closed)'
            || (\is_object($value) && \method_exists($value, '__toString')))) {
            // Native offsets coerce once and retain one byte, including NAN.
            $replacement = \is_float($value) && \is_nan($value) ? 'NAN' : (string) $value;
            if (\strlen($replacement) > 1) {
                $replacement = $replacement[0];
            }
        }
        $ftSettings[$offset] = $replacement;
        $settingsForDb = array('settings' => base64_encode(serialize($ftSettings)));

        ee()->db->where('name', $this->fieldtype_name)
            ->update('fieldtypes', $settingsForDb);

        return true;
    }

}
