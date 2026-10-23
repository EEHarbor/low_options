<?php

namespace LowOptionsTests;

/** Scripted EE model results retain the query contract for assertions. */
class Query
{
    public $calls = array();
    public $first = null;
    public $values = array();
    public $rows = array();
    public $num_rows = 0;
    public $record = array();
    public $currentRow = 0;
    private $rowData;
    public $collectionTrace;

    /**
     * Share collection observations across model-query clones.
     *
     * @return void
     */
    public function __construct()
    {
        $this->collectionTrace = (object) array('calls' => array());
    }

    /**
     * Record a fluent query operation.
     *
     * @param string $method
     * @param array $arguments
     * @return self
     */
    public function __call($method, $arguments)
    {
        $this->calls[] = array($method, $arguments);
        return $this;
    }

    /**
     * First.
     *
     * @return mixed
     */
    public function first()
    {
        return $this->first;
    }

    /**
     * All.
     *
     * @return self
     */
    public function all()
    {
        $this->collectionTrace->calls[] = array('all', spl_object_id($this));
        return $this;
    }

    /**
     * Return collection cardinality for model-query pagination.
     *
     * @return int
     */
    public function count()
    {
        $count = count($this->rows ?: $this->values);
        $this->collectionTrace->calls[] = array('count', spl_object_id($this), $count);
        return $count;
    }

    /**
     * Return the selected result column.
     *
     * @param string $field
     * @return array
     */
    public function pluck($field)
    {
        $this->calls[] = array('pluck', array($field));
        return $this->values;
    }

    /**
     * Num rows.
     *
     * @return int
     */
    public function num_rows()
    {
        return $this->num_rows;
    }

    /**
     * Result array.
     *
     * @return array
     */
    public function result_array()
    {
        return $this->rows ?: ($this->record ? array($this->record) : array());
    }

    /**
     * Result.
     *
     * @return array
     */
    public function result()
    {
        return array_map(function ($row) { return (object) $row; }, $this->result_array());
    }

    /**
     * Match EE's named-column, numeric-row and current-row behavior.
     *
     * @param int|string $index
     * @param string $type
     * @return mixed
     */
    public function row($index = 0, $type = 'object')
    {
        if (!is_numeric($index)) {
            if (!is_array($this->rowData)) {
                $this->rowData = $this->row_array(0);
            }
            if (array_key_exists($index, $this->rowData)) {
                return $this->rowData[$index];
            }
            $index = 0;
        }
        return $type === 'object' ? $this->row_object($index) : $this->row_array($index);
    }

    /**
     * Return an object row, preserving EE's current row for invalid indexes.
     *
     * @param int $index
     * @return object|array
     */
    public function row_object($index = 0)
    {
        $rows = $this->rows ?: ($this->record ? array($this->record) : array());
        if (!$rows) {
            return array();
        }
        if ($index != $this->currentRow && isset($rows[$index])) {
            $this->currentRow = $index;
        }
        return (object) $rows[$this->currentRow];
    }

    /**
     * Return one row as an array with the same current-row semantics.
     *
     * @param int $index
     * @return array
     */
    public function row_array($index = 0)
    {
        return (array) $this->row_object($index);
    }

}

class ModelService
{
    public $field;
    public $entries;
    public $calls = array();

    /**
     * Initialize the fixture collaborator state.
     *
     * @return void
     */
    public function __construct()
    {
        $this->field = new Query();
        $this->entries = new Query();
    }

    /**
     * Resolve the requested EE model query.
     *
     * @param string $name
     * @return Query
     */
    public function get($name)
    {
        $this->calls[] = $name;
        return $name === 'ChannelField' ? $this->field : $this->entries;
    }
}

class Database extends Query
{
    public $data_cache = array('table_names' => array());
    public $tableExists = false;
    public $count = 0;
    public $queryResult;
    public $fieldExists = true;
    public $sharedTrace;
    public $results = array();

    /**
     * Retrieve a scripted result for one table while retaining query arguments.
     *
     * @param string|null $table
     * @param int|null $limit
     * @param int|null $offset
     * @return Query
     */
    public function get($table = null, $limit = null, $offset = null)
    {
        $this->__call('get', func_get_args());
        return $this->resultFor($table);
    }

    /**
     * Retrieve table-specific results for an Active Record where lookup.
     *
     * @param string $table
     * @param array $conditions
     * @param int|null $limit
     * @param int|null $offset
     * @return Query
     */
    public function get_where($table, $conditions, $limit = null, $offset = null)
    {
        $this->__call('get_where', func_get_args());
        return $this->resultFor($table);
    }


    /**
     * Return a distinct result snapshot rather than the mutable query builder.
     *
     * @param string|null $table
     * @return Query
     */
    private function resultFor($table)
    {
        if ($table !== null && isset($this->results[$table])) {
            return $this->results[$table];
        }
        $result = new Query();
        $result->record = $this->record;
        $result->rows = $this->rows;
        $result->num_rows = $this->num_rows;
        return $result;
    }

    /**
     * Retain a trace shared with cloned query-builder instances.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->sharedTrace = (object) array('calls' => array());
    }

    /**
     * Record database builder calls both locally and across clones.
     *
     * @param string $method
     * @param array $arguments
     * @return self
     */
    public function __call($method, $arguments)
    {
        $this->sharedTrace->calls[] = array($method, $arguments);
        return parent::__call($method, $arguments);
    }


    /**
     * Report database schema-column availability.
     *
     * @param string $field
     * @param string $table
     * @return bool
     */
    public function field_exists($field, $table)
    {
        $this->calls[] = array("field_exists", array($field, $table));
        return $this->fieldExists;
    }

    /**
     * Report whether a settings table exists.
     *
     * @param string $table
     * @return bool
     */
    public function table_exists($table)
    {
        $this->calls[] = array('table_exists', array($table));
        return $this->tableExists;
    }

    /**
     * Count matched settings records.
     *
     * @param string $table
     * @return int
     */
    public function count_all_results($table)
    {
        $this->calls[] = array('count_all_results', array($table));
        return $this->count;
    }

    /**
     * Return a scripted raw category query result.
     *
     * @param string $sql
     * @return Query
     */
    public function query($sql)
    {
        $this->calls[] = array('query', array($sql));
        return $this->queryResult;
    }
}

class Template
{
    public $params = array();
    public $site_ids = array(1, 2);
    public $tagdata = '{option:value}:{option:label}';
    public $parsed = array();
    public $noResults = 0;

    /**
     * Follow EE's missing-parameter false/default contract.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function fetch_param($name, $default = false)
    {
        if (isset($this->params[$name]) && $name === 'url_title') {
            return $this->params[$name];
        }
        if (!isset($this->params[$name])) {
            return $default;
        }
        $value = $this->params[$name];
        switch ($value) {
            case 'y':
            case 'on':
                return 'yes';
            case 'n':
            case 'off':
                return 'no';
            default:
                return $value;
        }
    }

    /**
     * Capture the complete EE variable groups passed to its parser.
     *
     * @param string $tagdata
     * @param array $data
     * @return string
     */
    public function parse_variables($tagdata, $data)
    {
        $this->parsed[] = array($tagdata, $data);
        return json_encode($data);
    }

    /**
     * No results.
     *
     * @return string
     */
    public function no_results()
    {
        $this->noResults++;
        return 'NO RESULTS';
    }
}

class Environment
{
    public $TMPL;
    public $db;
    public $model;
    public $localize;
    public $config;
    public $load;
    public $logger;
    public $services = array();
    public $serviceCalls = array();
    public $formCalls = array();
    public $input;
    public $session;
    public $cache;
    public $cp;
    public $pagination;
    public $dbforge;
    public $view;
    public $functions;
    public $uri;
    public $legacy_api;
    public $api;
    public $security;
    public $addons_model;
    public $custom;

    /**
     * Initialize the fixture collaborator state.
     *
     * @return void
     */
    public function __construct()
    {
        $this->TMPL = new Template();
        $this->db = new Database();
        $this->db->queryResult = new Query();
        $this->model = new ModelService();
        $this->localize = (object) array('now' => 1700000000);
        $this->config = new Config();
        $this->load = new Loader();
        $this->logger = new Logger();
        $this->input = new Input();
        $this->session = new Session();
        $this->cache = new Cache();
        $this->cp = new Cp();
        $this->pagination = new Pagination();
        $this->dbforge = new Query();
        $this->functions = new Functions();
        $this->uri = new Uri();
        $this->legacy_api = new ServiceRecorder();
        $this->api = new ServiceRecorder();
        $this->security = new ServiceRecorder();
        $this->addons_model = new ServiceRecorder();
        $this->view = (object) array("cp_page_title" => "Title");
    }

    /**
     * Replace a native EE collaborator.
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function set($name, $value)
    {
        $this->$name = $value;
    }

    /**
     * Remove a native EE collaborator.
     *
     * @param string $name
     * @return void
     */
    public function remove($name)
    {
        $this->$name = null;
    }

}

class Config
{
    public $items = array('site_id' => 1, 'app_version' => '7.5.0');

    /**
     * Retrieve an EE configuration value.
     *
     * @param string $key
     * @return mixed
     */
    public function item($key)
    {
        return isset($this->items[$key]) ? $this->items[$key] : false;
    }
}

class Loader
{
    public $calls = array();

    /**
     * Record an EE helper or library request.
     *
     * @param string $method
     * @param array $arguments
     * @return void
     */
    public function __call($method, $arguments)
    {
        $this->calls[] = array($method, $arguments);
    }
}

class Logger
{
    public $messages = array();

    /**
     * Record a developer diagnostic.
     *
     * @param string $message
     * @return void
     */
    public function developer($message)
    {
        $this->messages[] = $message;
    }
}

class Input
{
    public $get = array();
    public $post = array();

    /**
     * Return one POST value with EE's missing-value and cleaning contracts.
     *
     * @param string $key
     * @param bool $clean
     * @return mixed
     */
    public function post($key, $clean = false)
    {
        return $this->fetch($this->post, $key, $clean);
    }

    /**
     * Prefer a present POST value, then fall back to GET.
     *
     * @param string $key
     * @param bool $clean
     * @return mixed
     */
    public function get_post($key, $clean = false)
    {
        return isset($this->post[$key]) ? $this->post($key, $clean) : $this->get($key, $clean);
    }

    /**
     * Return one GET value independently of POST.
     *
     * @param string $key
     * @param bool $clean
     * @return mixed
     */
    public function get($key, $clean = false)
    {
        return $this->fetch($this->get, $key, $clean);
    }

    /**
     * Match EE Input's presence and optional XSS-clean behavior.
     *
     * @param array $values
     * @param string $key
     * @param bool $clean
     * @return mixed
     */
    private function fetch(array $values, $key, $clean)
    {
        if (!isset($values[$key])) {
            return false;
        }
        return $clean === true ? \ee('Security/XSS')->clean($values[$key]) : $values[$key];
    }
}

class Url
{
    public $path;
    public $variables;

    /**
     * Retain URL components for exact assertions.
     *
     * @param string $path
     * @param array $variables
     * @return void
     */
    public function __construct($path = '', array $variables = array())
    {
        $this->path = $path;
        $this->variables = $variables;
    }

    /**
     * Create a control panel URL.
     *
     * @param string $path
     * @param array $variables
     * @return self
     */
    public function make($path, $variables = array())
    {
        return new self($path, $variables);
    }

    /**
     * Compile.
     *
     * @return string
     */
    public function compile()
    {
        return '/cp/' . $this->path;
    }
}

class Session
{
    public $cache = array();
    public $flash = array();

    /**
     * Retrieve one request-cache entry.
     *
     * @param string $module
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function cache($module, $key, $default = false)
    {
        return isset($this->cache[$module][$key]) ? $this->cache[$module][$key] : $default;
    }

    /**
     * Store one request-cache entry.
     *
     * @param string $module
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function set_cache($module, $key, $value)
    {
        $this->cache[$module][$key] = $value;
        return $value;
    }

    /**
     * Retain a flash message.
     *
     * @param string $type
     * @param string $message
     * @return void
     */
    public function set_flashdata($type, $message)
    {
        $this->flash[$type] = $message;
    }
}

class Cache
{
    public $data = array();

    /**
     * Read one cache entry.
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : false;
    }

    /**
     * Save one cache entry.
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function save($key, $value)
    {
        $this->data[$key] = $value;
        return true;
    }

    /**
     * Delete one cache entry.
     *
     * @param string $key
     * @return bool
     */
    public function delete($key)
    {
        unset($this->data[$key]);
        return true;
    }
}

class Pagination
{
    public $calls = array();

    /**
     * Record pagination options.
     *
     * @param string $method
     * @param array $arguments
     * @return self
     */
    public function __call($method, $arguments)
    {
        $this->calls[] = array($method, $arguments);
        return $this;
    }

    /**
     * Render a pagination marker.
     *
     * @param mixed $url
     * @return string
     */
    public function render($url)
    {
        $this->calls[] = array('render', array($url));
        return 'PAGINATION';
    }

    /**
     * Create links.
     *
     * @return string
     */
    public function create_links()
    {
        return 'PAGINATION';
    }
}

class Cp
{
    public $footer_item = array();
    public $nav = array();
    public $breadcrumbs = array();

    /**
     * Append control panel footer content.
     *
     * @param string $value
     * @return void
     */
    public function add_to_foot($value)
    {
        $this->footer_item[] = $value;
    }

    /**
     * Get foot.
     *
     * @return array
     */
    public function get_foot()
    {
        return $this->footer_item;
    }

    /**
     * Set EE2 navigation links.
     *
     * @param array $items
     * @return void
     */
    public function set_right_nav($items)
    {
        $this->nav = $items;
    }

    /**
     * Record a breadcrumb.
     *
     * @param mixed $url
     * @param string $title
     * @return void
     */
    public function set_breadcrumb($url, $title)
    {
        $this->breadcrumbs[] = array($url, $title);
    }
}

class ViewService
{
    public $template;
    public $variables;

    /**
     * Select a named EE view.
     *
     * @param string $template
     * @return self
     */
    public function make($template)
    {
        $this->template = $template;
        return $this;
    }

    /**
     * Select inline EE view content.
     *
     * @param string $template
     * @return self
     */
    public function makeFromString($template)
    {
        $this->template = $template;
        return $this;
    }

    /**
     * Capture variables passed to the view renderer.
     *
     * @param array $variables
     * @return string
     */
    public function render($variables)
    {
        $this->variables = $variables;
        return 'VIEW';
    }
}

class Redirect extends \RuntimeException
{
}

class Functions
{
    public $cleared = array();
    public $calls = array();
    public $result;

    /**
     * Capture framework parser delegation and return the configured result.
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        $this->calls[] = array($method, $arguments);
        return $this->result;
    }

    /**
     * Stop execution where EE's redirect terminates its request.
     *
     * @param mixed $url
     * @return void
     * @throws Redirect
     */
    public function redirect($url)
    {
        throw new Redirect($url instanceof Url ? $url->path : $url);
    }

    /**
     * Record cache invalidation on module installation.
     *
     * @param string $type
     * @return void
     */
    public function clear_caching($type)
    {
        $this->cleared[] = $type;
    }
}

class ServiceRecorder
{
    public $calls = array();
    public $result;

    /**
     * Return a configured collaborator result and retain exact arguments.
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        $this->calls[] = array($method, $arguments);
        return $this->result;
    }
}

class Alert
{
    public $calls = array();

    /**
     * Record an alert-builder call while retaining fluent chaining.
     *
     * @param string $method
     * @param array $arguments
     * @return self
     */
    public function __call($method, $arguments)
    {
        $this->calls[] = array($method, $arguments);
        return $this;
    }

    /**
     * Cannot Close.
     *
     * @return self
     */
    public function cannotClose()
    {
        $this->calls[] = array('cannotClose', array());
        return $this;
    }
}

class NavItem
{
    public $title;
    public $url;
    public $buttons = array();
    public $active = false;

    /**
     * Set a navigation item's URL.
     *
     * @param mixed $url
     * @return self
     */
    public function withUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Add one navigation action.
     *
     * @param string $title
     * @param mixed $url
     * @return self
     */
    public function withButton($title, $url)
    {
        $this->buttons[] = array($title, $url);
        return $this;
    }

    /**
     * Is Active.
     *
     * @return self
     */
    public function isActive()
    {
        $this->active = true;
        return $this;
    }
}

class Sidebar
{
    public $items = array();

    /**
     * Make.
     *
     * @return self
     */
    public function make()
    {
        return $this;
    }

    /**
     * Create one navigation heading.
     *
     * @param string $title
     * @return NavItem
     */
    public function addHeader($title)
    {
        $item = new NavItem();
        $item->title = $title;
        $this->items[] = $item;
        return $item;
    }
}

class Uri
{
    public $path = 'cp/addons/settings/low_options';

    /**
     * Segment array.
     *
     * @return array
     */
    public function segment_array()
    {
        return explode('/', $this->path);
    }

    /**
     * Uri string.
     *
     * @return string
     */
    public function uri_string()
    {
        return $this->path;
    }
}

class Formatter
{
    public $text;
    public $calls = array();

    /**
     * Retain the formatter's text and requested type.
     *
     * @param string $type
     * @param string $text
     * @return self
     */
    public function make($type, $text)
    {
        $this->calls[] = array('make', array($type, $text));
        $this->text = $text;
        return $this;
    }

    /**
     * Url Slug.
     *
     * @return self
     */
    public function urlSlug()
    {
        $this->calls[] = array('urlSlug', array());
        $this->text = str_replace(' ', '-', $this->text);
        return $this;
    }

    /**
     * Compile.
     *
     * @return string
     */
    public function compile()
    {
        return $this->text;
    }

    /**
     * Url.
     *
     * @return string
     */
    public function url()
    {
        return \prep_url($this->text);
    }
}

class IdentitySecurity
{
    /**
     * Preserve already-safe fixture input after observing EE's clean boundary.
     *
     * @param mixed $value
     * @return mixed
     */
    public function clean($value)
    {
        return $value;
    }
}
