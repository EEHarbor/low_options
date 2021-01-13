<?php if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Low Options Plugin class
 *
 * @package         low_options
 * @author          Lodewijk Schutte ~ Low <hi@gotolow.com>
 * @copyright       Copyright (c) 2011-2018, Lodewijk Schutte
 */
class Low_options
{

    // --------------------------------------------------------------------
    // PROPERTIES
    // --------------------------------------------------------------------

    /**
     * Requested field options
     *
     * @access      private
     * @var         array
     */
    private $field_options = array();

    /**
     * Query Builder
     *
     * @access      private
     * @var         object
     */
    private $builder;

    /**
     * Keep track of which tables were joined
     */
    private $joined = array();

    // --------------------------------------------------------------------
    // METHODS
    // --------------------------------------------------------------------

    /**
     * Call method
     *
     * @access      public
     * @param       string
     * @param       array
     * @return      string
     */
    public function __call($method, $arguments = array())
    {
        $this->_set_field_options($method);
        return $this->_parse_field_options();
    }

    /**
     * Pre EE2.5 method, uses field="" param
     *
     * @access      public
     * @return      string
     */
    public function get()
    {
        if ($field = ee()->TMPL->fetch_param('field')) {
            $this->_set_field_options($field);
        }

        return $this->_parse_field_options();
    }

    // --------------------------------------------------------------------

    /**
     * Get field options from cache or DB
     *
     * @access      private
     * @param       string
     * @return      void
     */
    private function _set_field_options($field_name)
    {
        // Serves as local cache
        static $fields = array();
        static $ids = array();

        // If this is an unknown field name, get it from the DB
        if (! array_key_exists($field_name, $fields)) {
            // Initiate options
            $options = array();

            // Site ids
            $site_ids = ee()->TMPL->site_ids;
            $site_ids[] = 0;

            // Get stuff from DB
            $row = ee('Model')
                ->get('ChannelField')
                ->fields('field_id', 'field_list_items', 'field_settings')
                ->filter('field_name', $field_name)
                ->filter('site_id', 'IN', $site_ids)
                ->first();

            // If we have a match, prep the options
            if ($row) {
                // Check default list items first
                if (! empty($row->field_list_items)) {
                    foreach (explode("\n", trim($row->field_list_items)) as $item) {
                        $options[$item] = $item;
                    }
                }
                // Check settings for 3rd party stuff
                else {
                    // Check for options
                    if (isset($row->field_settings['options']) && is_array($row->field_settings['options'])) {
                        $options = $row->field_settings['options'];
                    }
                    // EE3.5 value/label pairs
                    elseif (isset($row->field_settings['value_label_pairs']) && is_array($row->field_settings['value_label_pairs'])) {
                        $options = $row->field_settings['value_label_pairs'];
                    } else {
                        // If no actual options were found,
                        // populate options by getting the existing values of this field
                        $options = $this->_get_existing($row->field_id);

                        // Sort 'em
                        natcasesort($options);

                        // Possibly reverse 'em
                        if (ee()->TMPL->fetch_param('sort') == 'desc') {
                            $options = array_reverse($options);
                        }

                        // Set them both as keys and values
                        $options = array_combine($options, $options);
                    }
                }
            }

            // Add to local cache
            $fields[$field_name] = $options;
            $ids[$field_name]    = $row ? $row->field_id : 0;
        }

        // Set the options
        $this->field_options = $fields[$field_name];

        if (ee()->TMPL->fetch_param('show_empty') == 'no' && isset($ids[$field_name])) {
            $vals = $this->_get_existing($ids[$field_name]);
            $this->_filter($vals);
        }
    }

    // --------------------------------------------------------------------

    /**
     * Filter out non-existing stuff
     *
     * @access     private
     * @param      array
     * @return     void
     */
    private function _filter($some = array())
    {
        foreach ($this->field_options as $key => $val) {
            // 1 level nesting
            if (is_array($val)) {
                foreach ($val as $k => $v) {
                    if (! in_array($k, $some)) {
                        unset($this->field_options[$key][$k]);
                    }
                }
            } else {
                if (! in_array($key, $some)) {
                    unset($this->field_options[$key]);
                }
            }
        }

        // Danger! Anything with value '0' gets filtered out, too
        // Fix when needed
        $this->field_options = array_filter($this->field_options);
    }

    /**
     * Parse options
     *
     * @access      private
     * @return      string
     */
    private function _parse_field_options()
    {
        // Get optional ignore parameter
        list($ignore, $in) = $this->_explode_param(ee()->TMPL->fetch_param('ignore'));

        // Initiate variables
        $data = array();

        foreach ($this->field_options as $key => $val) {
            $options = array();

            if (is_array($val)) {
                foreach ($val as $k => $v) {
                    // Skip ignored
                    if (($in && in_array($k, $ignore)) || (! $in && ! in_array($k, $ignore))) {
                        continue;
                    }

                    $options[] = $this->_option($k, $v);
                }

                $group_name = $key;
            } else {
                // Skip ignored
                if (($in && in_array($key, $ignore)) || (! $in && ! in_array($key, $ignore))) {
                    continue;
                }

                $options[] = $this->_option($key, $val);
                $group_name = '';
            }

            $data[] = array(
                'option:group' => $group_name,
                'options' => $options
            );
        }

        return $data
            ? ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $data)
            : ee()->TMPL->no_results();
    }

    /**
     * Return an option row in array form
     *
     * @access      private
     * @param       string
     * @param       string
     * @return      array
     */
    private function _option($value, $label)
    {
        return array(
            'option:value' => $value,
            'option:label' => $label
        );
    }

    /**
     * Check the unique values for given field
     *
     * @access      private
     * @param       string
     * @param       string
     * @return      array
     */
    private function _get_existing($field_id)
    {
        // Initiate the quwery builder
        $this->builder = ee('Model')->get('ChannelEntry');

        // This is now!
        $now = ee()->localize->now;

        // Filter by site ID
        $this->builder->filter('site_id', 'IN', ee()->TMPL->site_ids);

        // The SQL format of the given field ID
        $field = 'field_id_'.$field_id;

        // Select the stuff
        $this->builder->fields('entry_id', 'channel_id', $field);

        // Only get non-empty field values, including 0 for numeric fields that default to 0
        $this->builder
            ->filter($field, '!=', '')
            ->filter($field, 'IS NOT', null);

        // Filter by channel name; needs join with Channel
        if ($val = ee()->TMPL->fetch_param('channel')) {
            $this->where('channel_name', $val, 'Channel');
        }

        // Filter by status; needs default value
        if ($val = ee()->TMPL->fetch_param('status', 'open')) {
            $this->where('status', $val);
        }

        // Filter by future entries
        if (ee()->TMPL->fetch_param('show_future_entries') != 'yes') {
            $this->builder->filter('entry_date', '<', $now);
        }

        // Filter by expired entries
        if (ee()->TMPL->fetch_param('show_expired') != 'yes') {
            $this->builder
                ->filterGroup()
                ->filter('expiration_date', 0)
                ->orFilter('expiration_date', '>', $now)
                ->endFilterGroup();
        }

        // Filter by category
        if ($val = ee()->TMPL->fetch_param('category')) {
            if (strpos($val, '&') > 0) {
                // Convert to array_pop
                $val = explode('&', $val);
                $val = array_filter($val, function ($v) {
                    return is_numeric($v);
                });

                // Execute query the old-fashioned way, so we don't interfere with active record
                // Get the entry ids that have all given categories assigned
                $q = ee()->db->query(
                    "SELECT entry_id, COUNT(*) AS num
					FROM exp_category_posts
					WHERE cat_id IN (".implode(',', $val).")
					GROUP BY entry_id HAVING num = ". count($val)
                );

                // If no entries are found, make sure we limit the query accordingly
                if ($q->num_rows()) {
                    $q = new \EllisLab\ExpressionEngine\Library\Data\Collection($q->result_array());
                    $entry_ids = $q->pluck('entry_id');
                } else {
                    $entry_ids = array(0);
                }

                $this->builder->filter('entry_id', 'IN', $entry_ids);
            } else {
                $this->where('cat_id', $val, 'Categories');
            }
        }

        $rows = $this->builder->all()->pluck($field);
        $vals = array();

        foreach ($rows as $row) {
            $split = strpos($row, "\n") ? "\n" : '|';
            $val   = explode($split, $row);
            $vals  = array_merge($vals, $val);
        }

        $vals = array_unique($vals);

        return $vals;
    }

    // --------------------------------------------------------------------

    /**
     * Simple where filter
     */
    private function where($key, $val, $with = null)
    {
        if ($with) {
            if (! in_array($with, $this->joined)) {
                $this->builder->with($with);
                $this->joined[] = $with;
            }

            $key = $with.'.'.$key;
        }

        list($val, $in) = $this->_explode_param($val);

        $this->builder->filter($key, ($in ? 'IN' : 'NOT IN'), $val);
    }

    // --------------------------------------------------------------------

    /**
     * Converts EE parameter to workable php vars
     *
     * @access      private
     * @param       string    String like 'not 1|2|3' or '40|15|34|234'
     * @return      array     [0] = array of ids, [1] = boolean whether to include or exclude: TRUE means include, FALSE means exclude
     */
    private function _explode_param($str)
    {
        // --------------------------------------
        // Initiate $in var to TRUE
        // --------------------------------------

        $in = true;

        // --------------------------------------
        // Check if parameter is "not bla|bla"
        // --------------------------------------

        if (strtolower(substr($str, 0, 4)) == 'not ') {
            // Change $in var accordingly
            $in = false;

            // Strip 'not ' from string
            $str = substr($str, 4);
        }

        // --------------------------------------
        // Return two values in an array
        // --------------------------------------

        return array(preg_split('/(&|\|)/', $str), $in);
    }

    // --------------------------------------------------------------------

    /**
     * Flatten a result set
     *
     * Given a DB result set, this will return an (associative) array
     * based on the keys given
     *
     * @param      array
     * @param      string    key of array to use as value
     * @param      string    key of array to use as key (optional)
     * @return     array
     */
    private function _flatten($resultset, $val, $key = false)
    {
        $array = array();

        foreach ($resultset as $row) {
            if ($key !== false) {
                $array[$row[$key]] = $row[$val];
            } else {
                $array[] = $row[$val];
            }
        }

        return $array;
    }

    // --------------------------------------------------------------------
}
