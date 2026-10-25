<?php

require 'phar://' . dirname(__DIR__, 2) . '/low_options/low_options.phar/FluxCapacitor/Conduit/GlobalFieldSettings.php';

class ExistingSettingsChild extends \Low\Options\FluxCapacitor\Conduit\GlobalFieldSettings
{
    /**
     * Retain an existing child method without a parent helper signature collision.
     *
     * @param mixed $value
     * @return mixed
     */
    public function integerKey($value)
    {
        return $value;
    }
}

$child = (new ReflectionClass(ExistingSettingsChild::class))->newInstanceWithoutConstructor();
echo json_encode(array('value' => $child->integerKey('existing child method')), JSON_THROW_ON_ERROR), "\n";
