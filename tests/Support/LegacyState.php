<?php

namespace LowOptionsTests;

final class LegacySession extends Session
{
    public $userdata = array();

    /**
     * Match EE's isset-based session default without losing numeric zero.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function userdata($name, $default = false)
    {
        return isset($this->userdata[$name]) ? $this->userdata[$name] : $default;
    }
}

final class LegacyLoader extends Loader
{
    public $response = 'VIEW';

    /**
     * Capture the bridge boundary rather than interpreting framework views.
     *
     * @param string $name
     * @param array $variables
     * @param bool $return
     * @return string|null
     */
    public function view($name, $variables = array(), $return = false)
    {
        $this->calls[] = array('view', array($name, $variables, $return));
        return $return === true ? $this->response : null;
    }
}


final class LegacyPagination extends Pagination
{
    /**
     * Record the rendered-link boundary while retaining its configured result.
     *
     * @return string
     */
    public function create_links()
    {
        $this->calls[] = array('create_links', array());
        return parent::create_links();
    }
}
