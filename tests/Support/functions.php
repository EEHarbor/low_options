<?php

    /**
     * Resolve EE services used by the shipped add-on.
     *
     * @param string|null $service
     * @return mixed
     */
    function ee($service = null)
    {
        $ee = $GLOBALS['low_options_ee'];
        if ($service === null) {
            return $ee;
        }
        $ee->serviceCalls[] = array($service, array_slice(func_get_args(), 1));
        if ($service === 'Model') {
            return $ee->model;
        }
        if ($service === 'db') {
            return $ee->db->newQuery();
        }
        if ($service === 'CP/URL') {
            $arguments = func_get_args();
            return new \LowOptionsTests\Url(isset($arguments[1]) ? $arguments[1] : '');
        }
        if (isset($ee->services[$service])) {
            return $ee->services[$service];
        }
        throw new \LogicException('Unconfigured EE service: ' . $service);
    }


/**
 * Read one cache fixture using EE's file-helper return contract.
 *
 * @param string $file
 * @return string|false
 */
function read_file($file)
{
    return file_exists($file) ? file_get_contents($file) : false;
}

/**
 * Match EE's file-open failure boundary and locked writes.
 *
 * @param string $file
 * @param string $data
 * @param string $mode
 * @return bool
 */
function write_file($file, $data, $mode = 'wb')
{
    if (is_dir($file) || !is_dir(dirname($file)) || (file_exists($file) && !is_writable($file))) {
        return false;
    }
    $handle = fopen($file, $mode);
    if ($handle === false) {
        return false;
    }
    flock($handle, LOCK_EX);
    fwrite($handle, $data);
    flock($handle, LOCK_UN);
    fclose($handle);
    return true;
}

/**
 * Record a form-open call without making an HTTP request.
 *
 * @param string $action
 * @param array $attributes
 * @return string
 */
function form_open($action, $attributes)
{
    ee()->formCalls[] = array('open', $action, $attributes);
    return '<form action="' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Record an EE text-input helper call.
 *
 * @param string $name
 * @param mixed $value
 * @return string
 */
function form_input($name, $value)
{
    ee()->formCalls[] = array('input', $name, $value);
    return '<input name="' . $name . '" value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Record an EE checkbox helper call.
 *
 * @param string $name
 * @param string $value
 * @param mixed $checked
 * @return string
 */
function form_checkbox($name, $value, $checked)
{
    ee()->formCalls[] = array('checkbox', $name, $value, $checked);
    return '<input type="checkbox" name="' . $name . '">';
}

/**
 * Close the fixture form.
 *
 * @return string
 */
function form_close()
{
    return '</form>';
}


/**
 * Return the request URI used by the fieldtype's create-entry fallback.
 *
 * @return string
 */
function uri_string()
{
    return ee()->uri->path;
}

/**
 * Mirror CI's URL helper normalization used by the bridge wrapper.
 *
 * @param string $text
 * @return string
 */
function url_title($text)
{
    return str_replace(' ', '-', $text);
}

/**
 * Return an explicit-scheme URL for bridge delegation assertions.
 *
 * @param string $url
 * @return string
 */
function prep_url($url)
{
    return strpos($url, '://') === false ? 'http://' . $url : $url;
}

/**
 * Collapse path slashes while preserving the URL scheme separator.
 *
 * @param string $url
 * @return string
 */
function reduce_double_slashes($url)
{
    return preg_replace('#(?<!:)//+#', '/', $url);
}
