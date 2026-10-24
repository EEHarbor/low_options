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
     * Return saved values while preserving custom ArrayAccess getters.
     *
     * @param string $name
     * @return mixed
     */
    public function getSetting($name)
    {
        $ftSettings = $this->getSettings();
        if ($ftSettings) {
            if (is_array($ftSettings) && !array_key_exists($name, $ftSettings)) {
                return null;
            }
            return $ftSettings[$name];
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
