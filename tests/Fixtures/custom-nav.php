<?php

namespace Low\Options\Conduit;

class McpNav extends \Low\Options\FluxCapacitor\Conduit\McpNav
{
    /**
     * Provide installed-addon navigation from the current fixture request.
     *
     * @return array
     */
    protected function defaultItems()
    {
        return $GLOBALS['nav_fixture']['items'];
    }

    /**
     * Provide action buttons keyed to existing navigation items.
     *
     * @return array
     */
    protected function defaultButtons()
    {
        return $GLOBALS['nav_fixture']['buttons'];
    }

    /**
     * Map nested pages to their parent navigation item.
     *
     * @return array
     */
    protected function defaultActiveMap()
    {
        return $GLOBALS['nav_fixture']['map'];
    }

    /**
     * Allow an installed add-on to generate its navigation later.
     *
     * @return bool
     */
    public function deferGenerate()
    {
        return $GLOBALS['nav_fixture']['defer'];
    }
}

class EmptyNav extends \Low\Options\FluxCapacitor\Conduit\McpNav
{
}
