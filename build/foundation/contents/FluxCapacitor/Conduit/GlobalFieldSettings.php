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
        if (is_array($ftSettings)) {
            if (is_bool($name) || $name === null) {
                $name = $name === null ? '' : (int) $name;
            }
            if (is_float($name) && is_finite($name) && $name >= PHP_INT_MIN && $name < PHP_INT_MAX && floor($name) === $name) {
                $name = (int) $name;
            }
        }
        if ((is_array($ftSettings) || is_string($ftSettings)) && (is_int($name) || is_string($name))) {
            return isset($ftSettings[$name]) ? $ftSettings[$name] : null;
        }
        return null;
    }

    /**
     * Read native legacy settings while normalizing malformed or empty storage.
     *
     * @return mixed
     */
    public function getSettings()
    {
        $ftSettingsQuery = ee()->db->select('settings')
            ->where('name', $this->fieldtype_name)
            ->get('fieldtypes');

        if ((bool) $ftSettingsQuery->num_rows()) {
            $ftSettings = SerializedValue::decode(base64_decode((string) $ftSettingsQuery->row('settings'))) ?: array();
        } else {
            return null;
        }

        return $ftSettings;
    }

    public function updateSetting($key, $value)
    {
        $ftSettings = $this->getSettings();

        if (!is_null($ftSettings)) {
            $ftSettings[$key] = $value;
            $settingsForDb = array('settings' => base64_encode(serialize($ftSettings)));

            ee()->db->where('name', $this->fieldtype_name)
                ->update('fieldtypes', $settingsForDb);
        } else {
            return false;
        }

        return true;
    }
}
