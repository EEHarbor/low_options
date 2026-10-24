<?php
namespace @@@ADDON_NAMESPACE@@@\FluxCapacitor\Base;

use @@@ADDON_NAMESPACE@@@\FluxCapacitor\FluxCapacitor;

/**
 * EEHarbor Pi parent class
 *
 * @package         EEHarbor plugin Parent
 * @version         @@@FOUNDATION_VERSION@@@
 * @author          Tom Jaeger <Tom@EEHarbor.com>
 * @link            https://eeharbor.com
 * @copyright       Copyright (c) 2018, Tom Jaeger/EEHarbor
 */

class Pi
{
    public $flux;
    public $version;

    public function __construct()
    {
        $this->flux = new FluxCapacitor;
        $this->version = $this->flux->getConfig('version');
    }

    /**
     * Return plugin metadata without requiring an instance.
     *
     * @return array
     */
    public static function info()
    {
        $flux = new FluxCapacitor;
        return array(
            'pi_name'           => $flux->getConfig('name'),
            'pi_version'        => $flux->getConfig('version'),
            'pi_author'         => 'EEHarbor',
            'pi_author_url'     => 'https://eeharbor.com',
            'pi_description'    => $flux->getConfig('description')
        );
    }
}
