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
            return $ftSettings[$name];
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
     * Read native legacy settings while normalizing malformed or empty storage.
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
            // Native call sites bind outer, then inner, before reading the row.
            $hooks = array(
                \function_exists(__NAMESPACE__ . '\\unserialize'),
                \function_exists(__NAMESPACE__ . '\\base64_decode')
            );
            $hooks = SerializedValue::bindCaller(__METHOD__, $hooks);
            $stored = $ftSettingsQuery->row('settings');
            $decoded = $hooks[1]
                ? base64_decode($stored) : \base64_decode($stored === null ? '' : $stored);
            if ($hooks[0]) {
                $ftSettings = unserialize($decoded);
            } else {
                if (\is_object($decoded) && \method_exists($decoded, '__toString')) {
                    $decoded = (string) $decoded;
                }
                $ftSettings = \is_string($decoded) ? SerializedValue::decode($decoded) : \unserialize($decoded);
            }
            $ftSettings = $ftSettings ?: array();
        } else {
            return null;
        }

        return $ftSettings;
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
        // Preserve PHP 7.4 integer-width keys without an out-of-range cast.
        if (\is_array($ftSettings) && \is_float($key) && !\is_finite($key)) {
            $key = 0;
        }
        if (\is_array($ftSettings) && \is_float($key)) {
            $width = 2.0 ** (\PHP_INT_SIZE * 8);
            $key = \fmod($key, $width);
            if ($key >= $width / 2) {
                $key -= $width;
            }
            if ($key < -$width / 2) {
                $key += $width;
            }
            $key = (int) $key;
        }
        if (\is_array($ftSettings) && $key === null) {
            $key = '';
        }
        $stringOffset = null;
        if (\is_string($ftSettings) && (\is_int($key) || (\is_string($key) && \preg_match('/^[\x09-\x0d ]*[+-]?[0-9]+$/D', $key)))) {
            $stringOffset = $key + 0;
        }
        if (\is_int($stringOffset) && $stringOffset >= -\strlen($ftSettings) && $stringOffset < \PHP_INT_MAX
            && (\is_scalar($value) || \is_resource($value) || \gettype($value) === 'resource (closed)'
            || (\is_object($value) && \method_exists($value, '__toString')))) {
            // Native offsets coerce once and retain one byte, including NAN.
            $value = \is_float($value) && \is_nan($value) ? 'NAN' : (string) $value;
            if (\strlen($value) > 1) {
                $value = $value[0];
            }
        }
        $ftSettings[$key] = $value;
        $settingsForDb = array('settings' => base64_encode(serialize($ftSettings)));

        ee()->db->where('name', $this->fieldtype_name)
            ->update('fieldtypes', $settingsForDb);

        return true;
    }

}
