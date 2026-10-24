<?php
namespace @@@ADDON_NAMESPACE@@@\FluxCapacitor\Base;

use @@@ADDON_NAMESPACE@@@\FluxCapacitor\FluxCapacitor;
use @@@ADDON_NAMESPACE@@@\FluxCapacitor\Conduit\Version;

/**
 * EEHarbor update parent class
 *
 * @package         EEHarbor Update Parent
 * @version         @@@FOUNDATION_VERSION@@@
 * @author          Tom Jaeger <Tom@EEHarbor.com>
 * @link            https://eeharbor.com
 * @copyright       Copyright (c) 2018, Tom Jaeger/EEHarbor
 */

class Upd
{
    public $flux;
    public $has_cp_backend = 'y';
    public $has_publish_fields = 'n';
    public $ext_settings='n';
    public $version;
    public $module_name;

    public function __construct()
    {
        $this->flux = new FluxCapacitor;
        $this->version = $this->flux->getConfig('version');
        $this->module_name = str_replace('_upd', '', get_class($this));

        $version = new Version;
        $version->check();
    }

    public function install()
    {
        // Ext settings, so don't insert it into modules table
        if($this->ext_settings === 'y') {
            return true;
        }

        $mod_data = array(
            'module_name'        => $this->module_name,
            'module_version'     => $this->version,
            'has_cp_backend'     => $this->has_cp_backend,
            'has_publish_fields' => $this->has_publish_fields,
        );

        ee()->functions->clear_caching('db');
        ee()->db->insert('modules', $mod_data);

        return true;
    }

    /**
     * Update an installed add-on and load an extension only when present.
     *
     * @param string $current
     * @return bool
     */
    public function update($current = '')
    {
        if ($current == '' or version_compare($current, $this->version, '==')) {
            return false;
        }

        // If you add an extension after installing, it just doesnt work.
        try{
            $extension_path = PATH_THIRD . '@@@MODULE@@@/ext.@@@MODULE@@@.php';
            if (is_file($extension_path)) {
                include_once $extension_path;
            }

            $class = $this->module_name . '_ext';

            if (class_exists($class, true)) {
                $ext = new $class;
                $ext->updateExtension($current);
            }
        } catch (Exception $e) {
        }

        return true;
    }

    public function uninstall()
    {
        $this->module_name = str_replace('_upd', '', get_class($this));

        // --------------------------------------
        // Get module ID
        // --------------------------------------

        $module_id = ee()->db->select('module_id')
            ->from('modules')
            ->where('module_name', $this->module_name)
            ->get()->row('module_id');

        // --------------------------------------
        // Remove references from module_member_groups
        // --------------------------------------

        $module_member_groups = version_compare(APP_VER, '6.0', '>=') ? 'module_member_roles' : 'module_member_groups';
        ee()->db->where('module_id', $module_id);
        ee()->db->delete($module_member_groups);

        // --------------------------------------
        // Remove references from modules
        // --------------------------------------

        ee()->db->where('module_name', $this->module_name);
        ee()->db->delete('modules');

        // --------------------------------------
        // Remove references from actions
        // --------------------------------------

        ee()->db->where('class', $this->module_name);
        ee()->db->delete('actions');

        // --------------------------------------
        // Remove references from extensions
        // --------------------------------------

        ee()->db->where('class', $this->module_name . '_ext');
        ee()->db->delete('extensions');

        // If the user installed the add-on menu item, we need to remove it.
        if (!$this->flux->is_ee2()) {
            ee()->db->where('data', $this->module_name . '_ext');
            ee()->db->delete('menu_items');
        }

        return true;
    }
}
