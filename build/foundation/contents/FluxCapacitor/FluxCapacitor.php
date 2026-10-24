<?php

namespace @@@ADDON_NAMESPACE@@@\FluxCapacitor;

use @@@ADDON_NAMESPACE@@@\FluxCapacitor\Bridge\EE;

/**
 * Flux Capacitor
 *
 * @package         Flux Capacitor
 * @version         @@@FOUNDATION_VERSION@@@
 * @author          Tom Jaeger <Tom@EEHarbor.com>
 * @link            https://eeharbor.com
 * @copyright       Copyright (c) 2018, Tom Jaeger/EEHarbor
 */
class FluxCapacitor extends EE
{
    const L = @@@LICENSE_CONSTANT@@@;

    public function __construct()
    {
        $params = array("module" => "@@@MODULE@@@", "module_name" => "@@@MODULE_NAME@@@");
        parent::__construct($params);
    }
}
