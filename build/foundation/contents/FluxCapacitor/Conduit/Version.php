<?php

namespace @@@ADDON_NAMESPACE@@@\FluxCapacitor\Conduit;

use @@@ADDON_NAMESPACE@@@\FluxCapacitor\FluxCapacitor;
use @@@ADDON_NAMESPACE@@@\FluxCapacitor\Conduit\StaticCache;

/**
 * Version class
 *
 * @package         Version
 * @version         @@@FOUNDATION_VERSION@@@
 * @author          Tom Jaeger <Tom@EEHarbor.com>
 * @link            https://eeharbor.com
 * @copyright       Copyright (c) 2018, Tom Jaeger/EEHarbor
 */
class Version
{
    // Assume the license table is corrupt until we can verify otherwise.
    public $flux;
    private $table_corrupt = true;
    private $public_key = 'LS0tLS1CRUdJTiBQVUJMSUMgS0VZLS0tLS0KTUlJQ0lqQU5CZ2txaGtpRzl3MEJBUUVGQUFPQ0FnOEFNSUlDQ2dLQ0FnRUF6UXZFMWpqdU5wZXVZUjhYY2dYbQpqamRVZ2NlT1NyY0xZRmJKNTA2TGNNQkFsMTBxV3E2aU5NRVJNbXAzNno2SWhvVG4xdVNYS2llY0NUd21ZUE9hCjZVaXZIaFJkYVlLdVMzMEN1U0wzTzJ4aVFBditrdW5aQkk3c1lsL0R5V1ljdVV1ckswdFlDSm1jUjQxd0dwU2oKYTVGblZja2hndE12d0ZWTGZXWGZ6ck5MTjdJZzJSVFdCNWlnSCtEaGlQVE5jNGRWR1FXSjI4RHBhMDBreFRjNAp3SHNGUGFHWDBndER3VzZ0ZUNnWFFzbkJmdVVSNE85bkMyVERINUZyakFrenNRa01nNDFBQnFyVFhVdWVaYkxnCjNkUUxUWm52eWxQYmRoamdEbCs5SzF3amt2dDl2Wlh1SFVaeTNneG1tYy8zaHU3M0w0RTVWRkdmRWxLZ0hiNngKbVBoajZVSlM1dWZuZ1VPcXdxNnhKZTd5NlhWTnJrem9ubWVQd2VSeHFsS3QxMzN1QXg5S3BRTEJOdWc1QmlvRQpjSHl2ZUFBZDVXOURKbStSS0ZaaG1XODdlcFBkczRpL0FDMnl2UGMxNEgwWGVOL2JuUDV2UlRDcitzaFNzWVN1CkxwTUsvRVNSRDJiOUxxNzhPVElaNG5WTXpzYk5xM3NKS1pwUUFsQnExTEIxSmlrSHRUdjNPK3pjbHpZeWNQbFYKaGRGRFk2QlBkMTNBYjdaQzZYOE5jTWpVN2V3L3pFMC95ME9tVHF2M044N0xTMXo0TmhoWjVySVBlbTd0cEVTQgpuaGgwTzUzcUhSR0JWV0U0eHZnV1VuNTgyK21RS0FuZW9COHVuSGFnQmI3M2VxRTFPMTErOUpSSWp2c1dzd21WCmdLbkJnVXZsdjlTS08xNzIvSVZrT2hNQ0F3RUFBUT09Ci0tLS0tRU5EIFBVQkxJQyBLRVktLS0tLQo=';

    public $pro;
    public function __construct()
    {
        $this->flux = new FluxCapacitor;
        $this->verifyLicenseTable();
    }

    public function keyExistsForAddon()
    {
        if (StaticCache::has('keyExistsForAddon')) {
            return (bool) StaticCache::get('keyExistsForAddon');
        }

        // Find out if there's an existing license for this add-on on this site.
        ee()->db->where('site_id', ee()->config->item('site_id'));
        ee()->db->where('addon', '@@@ADDON_NAMESPACE@@@');
        $count = ee()->db->count_all_results('eeharbor_licenses');

        StaticCache::put('keyExistsForAddon', $count);

        // Returns true or false if it exists
        return ($count > 0);
    }

    public function saveLicenseKey(array $data, $addon = '@@@ADDON_NAMESPACE@@@')
    {
        // Find out if there's an existing license for this add-on on this site.
        $exists = $this->keyExistsForAddon();

        // Set variables if they dont exist
        $data['site_id'] = isset($data['site_id']) ? $data['site_id'] : ee()->config->item('site_id');
        $data['addon'] = isset($data['addon']) ? $data['addon'] : $addon;

        // If it exists, update it, otherwise, insert it.
        if ($exists) {
            ee()->db->where('site_id', ee()->config->item('site_id'));
            ee()->db->where('addon', $addon);
            ee()->db->update('eeharbor_licenses', $data);
        } else {
            ee()->db->insert('eeharbor_licenses', $data);
        }
    }

    public function deleteLicenseKey()
    {
        ee()->db->where('site_id', ee()->config->item('site_id'));
        ee()->db->where('addon', '@@@ADDON_NAMESPACE@@@');
        ee()->db->delete('eeharbor_licenses');
    }

    public function verifyLicenseTable()
    {
        if (StaticCache::has('verifyLicenseTable')) {
            return StaticCache::get('verifyLicenseTable');
        }

        // EE caches the list of DB tables, so unset the table_names var if it's set
        // otherwise table_exists could return a false negative if it was just created.
        if (isset(ee()->db->data_cache['table_names'])) {
            unset(ee()->db->data_cache['table_names']);
        }

        // Make sure the eeharbor_licenses table exists.
        if (ee()->db->table_exists('eeharbor_licenses') === false) {
            ee()->load->dbforge();

            // Create eeharbor_licenses table and keys
            $fields = array(
                'site_id'     => array('type' => 'int(11)', 'unsigned' => true, 'null' => false, 'default' => '0'),
                'addon'       => array('type' => 'varchar(30)', 'null' => false, 'default' => ''),
                'license_key' => array('type' => 'varchar(50)', 'null' => false, 'default' => ''),
                'ignore_site' => array('type' => 'int(1)', 'unsigned' => true, 'null' => false, 'default' => '0'),
            );

            ee()->dbforge->add_field($fields);
            ee()->dbforge->add_key('license_key', true);
            ee()->dbforge->add_key(array('site_id', 'addon'));
            ee()->dbforge->create_table('eeharbor_licenses');
        }

        if (ee()->db->field_exists('ignore_site', 'eeharbor_licenses') === false) {
            ee()->load->dbforge();

            $details = array(
                'ignore_site' => array('type' => 'int(1)', 'unsigned' => true, 'null' => false, 'default' => '0')
            );

            ee()->dbforge->add_column('eeharbor_licenses', $details);
        }

        $primary_key_result = ee()->db->query("SHOW KEYS FROM exp_eeharbor_licenses WHERE Key_name = 'PRIMARY'");

        if ($primary_key_result->num_rows() == 1) {
            $primary_key_info = $primary_key_result->row();

            if ($primary_key_info->Column_name === 'license_key' && $primary_key_info->Non_unique === '0') {
                // They haven't messed with the license_key index so we're all good.
                $this->table_corrupt = false;
            }
        }

        StaticCache::set('verifyLicenseTable', $this->table_corrupt);

        return $this->table_corrupt;
    }

    /**
     * Build the license payload with the queried Pro version.
     *
     * @return bool|null
     */
    public function check()
    {
        if (!empty($_GET['ldev']) && $_GET['ldev'] == 1) {
            $ping_server_url = 'http://localhost:2368/update.gif';
        } else {
            $ping_server_url = 'https://ping.eeharbor.com/update.gif';
        }

        $update_pixel = $this->spiderSafe($ping_server_url);

        if (StaticCache::has('version_pro')) {
            $this->pro = StaticCache::get('version_pro');
        } else {
            $this->pro = ee()->db->select('module_version')->where('module_name', 'Pro')->get('modules')->row();
            StaticCache::set('version_pro', $this->pro);
        }

        $payload_data = array(
            'api' => '1', // API Version
            'l'   => $this->getLicenseKey(), // License
            'a'   => "@@@MODULE@@@", // Add-on name
            'v'   => $this->flux->getConfig('version'), // Add-on Version
            'e'   => $this->flux->getEEVersion(false), // EE Version
            'p'   => phpversion(),
            'd'   => ee()->config->item('base_url'), // Domain
            'c'   => $this->table_corrupt,
            's'   => ee()->config->item('site_id'),
            'pi'  => !empty($this->pro) ? $this->pro->module_version : 0,
            'pl'  => ee()->config->item('site_license_key') ?: 0,
            'lc'  => FluxCapacitor::L
        );

        $payload_raw = json_encode($payload_data);

        // Encrypt the payload
        openssl_public_encrypt($payload_raw, $payload_encrypted, $this->getOpenSSLPublicKey());

        $payload_hex = bin2hex($payload_encrypted);

        $pixel_styles = 'width:0;height:0;overflow:hidden;background:url(' . $update_pixel . '?' . $payload_hex;
        $js_for_footer = '<div id="@@@MODULE@@@__up" style="' . $pixel_styles . '"></div>';

        // This is the ee cp js footer. If we find the version js, it means we already added it, so lets return early
        foreach ($this->flux->getFooter() as $foot) {
            if (strpos($foot, '<div id="@@@MODULE@@@__up" style="') !== false) {
                // Looks like we found the version js code! Lets not add it to footer again
                return true;
            }
        }

        ee()->cp->add_to_foot($js_for_footer);

        $js = <<<'JS'
@@@VALIDATION_JS@@@
JS;

        if (FluxCapacitor::L) {
            $this->flux->javascript_to_page($js);
        }
    }

    public function getLicenseKey()
    {
        ee()->db->where('site_id', ee()->config->item('site_id'));
        ee()->db->where('addon', '@@@ADDON_NAMESPACE@@@');
        $license_info = ee()->db->get('eeharbor_licenses', 1)->row();

        $license_key = '';
        if (!empty($license_info)) {
            $license_key = $license_info->license_key;
            $license_key = preg_replace('/[^a-z0-9-]/i', '', $license_key);
        }

        return $license_key;
    }

    public function getIgnoreSite()
    {
        ee()->db->where('site_id', ee()->config->item('site_id'));
        ee()->db->where('addon', '@@@ADDON_NAMESPACE@@@');
        $license_info = ee()->db->get('eeharbor_licenses', 1)->row();

        $ignore_site = false;
        if (!empty($license_info)) {
            $ignore_site = $license_info->ignore_site;
        }

        return $ignore_site;
    }

    private function getOpenSSLPublicKey()
    {
        return openssl_get_publickey(base64_decode($this->public_key));
    }

    private function spiderSafe($string)
    {
        $convertedString = "";
        for ($i = 0; $i < strlen($string); $i++) {
            $char = $string[$i];
            $convertedString .= "&#" . ord($char) . ";";
        }
        return $convertedString;
    }
}
