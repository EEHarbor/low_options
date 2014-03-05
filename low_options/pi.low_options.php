<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// Provide info to EE
$plugin_info = array(
	'pi_name'        => 'Low Options',
	'pi_version'     => '0.2.0',
	'pi_author'      => 'Lodewijk Schutte ~ Low',
	'pi_author_url'  => 'http://gotolow.com/',
	'pi_description' => 'Get options from select field.',
	'pi_usage'       => 'See https://github.com/low/low_options for more details.'
);

/**
 * < EE 2.6.0 backward compat
 */
if ( ! function_exists('ee'))
{
	function ee()
	{
		static $EE;
		if ( ! $EE) $EE = get_instance();
		return $EE;
	}
}

/**
 * Low Options Plugin class
 *
 * @package         low_options
 * @author          Lodewijk Schutte ~ Low <hi@gotolow.com>
 * @copyright       Copyright (c) 2011-2012, Lodewijk Schutte
 */
class Low_options {

	// --------------------------------------------------------------------
	// PROPERTIES
	// --------------------------------------------------------------------

	/**
	 * Plugin return data
	 *
	 * @access      public
	 * @var         string
	 */
	public $return_data;

	// --------------------------------------------------------------------

	/**
	 * Requested field options
	 *
	 * @access      private
	 * @var         array
	 */
	private $field_options = array();

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
		if ($field = ee()->TMPL->fetch_param('field'))
		{
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
		if ( ! isset($fields[$field_name]))
		{
			// Initiate options
			$options = array();

			// Get stuff from DB
			$site_id = ee()->config->item('site_id');
			$query = ee()->db->select('field_id, field_list_items, field_settings')
			       ->from('channel_fields')
			       ->where('field_name', $field_name)
			       ->where('site_id', $site_id)
			       ->limit(1)
			       ->get();

			// If we have a match, prep the options
			if ($row = $query->row())
			{
				// Check default list items first
				if ( ! empty($row->field_list_items))
				{
					foreach (explode("\n", $row->field_list_items) AS $item)
					{
						$options[$item] = $item;
					}
				}
				// Check settings for 3rd party stuff
				else
				{
					// Decode settings
					$settings = @unserialize(base64_decode($row->field_settings));

					// Check for options
					if (isset($settings['options']))
					{
						$options = $settings['options'];
					}
				}
			}

			// Add to local cache
			$fields[$field_name] = $options;
			$ids[$field_name] = $query->row('field_id');
		}

		// Set the options
		$this->field_options = $fields[$field_name];

		if (ee()->TMPL->fetch_param('show_empty') == 'no' && isset($ids[$field_name]))
		{
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
		foreach ($this->field_options AS $key => $val)
		{
			// 1 level nesting
			if (is_array($val))
			{
				foreach ($val AS $k => $v)
				{
					if ( ! in_array($k, $some))
					{
						unset($this->field_options[$key][$k]);
					}
				}
			}
			else
			{
				if ( ! in_array($key, $some))
				{
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

		foreach ($this->field_options AS $key => $val)
		{
			$options = array();

			if (is_array($val))
			{
				foreach ($val AS $k => $v)
				{
					// Skip ignored
					if (($in && in_array($k, $ignore)) || ( ! $in && ! in_array($k, $ignore))) continue;

					$options[] = $this->_option($k, $v);
				}

				$group_name = $key;
			}
			else
			{
				// Skip ignored
				if (($in && in_array($key, $ignore)) || ( ! $in && ! in_array($key, $ignore))) continue;

				$options[] = $this->_option($key, $val);
				$group_name = '';
			}

			$data[] = array(
				'option:group' => $group_name,
				'options' => $options
			);
		}

		$this->return_data
			= ($data)
			? ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $data)
			: ee()->TMPL->no_results();

		return $this->return_data;
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
		// --------------------------------------
		// Start composing query
		// --------------------------------------

		$sql_now   = ee()->localize->now;
		$sql_field = 'field_id_'.$field_id;

		ee()->db->select("DISTINCT({$sql_field}) AS val")
		     ->from('channel_data d')
		     ->join('channel_titles t', 'd.entry_id = t.entry_id')
		     ->where($sql_field.' !=', '');

		// --------------------------------------
		// Filter by channel
		// --------------------------------------

		if ($channels = ee()->TMPL->fetch_param('channel'))
		{
			// Determine which channels to filter by
			list($channels, $in) = $this->_explode_param($channels);

			// Join channels table
			ee()->db->join('channels c', 't.channel_id = c.channel_id');
			ee()->db->{($in ? 'where_in' : 'where_not_in')}('c.channel_name', $channels);
		}

		// --------------------------------------
		// Filter by site
		// --------------------------------------

		ee()->db->where_in('t.site_id', ee()->TMPL->site_ids);

		// --------------------------------------
		// Filter by status - defaults to open
		// --------------------------------------

		if ($status = ee()->TMPL->fetch_param('status', 'open'))
		{
			// Determine which statuses to filter by
			list($status, $in) = $this->_explode_param($status);

			// Adjust query accordingly
			ee()->db->{($in ? 'where_in' : 'where_not_in')}('t.status', $status);
		}

		// --------------------------------------
		// Filter by expired entries
		// --------------------------------------

		if (ee()->TMPL->fetch_param('show_expired') != 'yes')
		{
			ee()->db->where("(t.expiration_date = '0' OR t.expiration_date > '{$sql_now}')");
		}

		// --------------------------------------
		// Filter by future entries
		// --------------------------------------

		if (ee()->TMPL->fetch_param('show_future_entries') != 'yes')
		{
			ee()->db->where("t.entry_date < '{$sql_now}'");
		}

		// --------------------------------------
		// Filter by category
		// --------------------------------------

		if ($categories_param = ee()->TMPL->fetch_param('category'))
		{
			// Determine which categories to filter by
			list($categories, $in) = $this->_explode_param($categories_param);

			if (strpos($categories_param, '&'))
			{
				// Execute query the old-fashioned way, so we don't interfere with active record
				// Get the entry ids that have all given categories assigned
				$query = ee()->db->query(
					"SELECT entry_id, COUNT(*) AS num
					FROM exp_category_posts
					WHERE cat_id IN (".implode(',', $categories).")
					GROUP BY entry_id HAVING num = ". count($categories));

				// If no entries are found, make sure we limit the query accordingly
				if ( ! ($entry_ids = low_flatten_results($query->result_array(), 'entry_id')))
				{
					$entry_ids = array(0);
				}

				ee()->db->where_in('entry_id', $entry_ids);
			}
			else
			{
				// Join category table
				ee()->db->join('category_posts cp', 'cp.entry_id = t.entry_id');
				ee()->db->{($in ? 'where_in' : 'where_not_in')}('cp.cat_id', $categories);
			}
		}

		// --------------------------------------
		// Get results
		// --------------------------------------

		$query = ee()->db->get();
		$vals = array();

		foreach ($query->result() AS $row)
		{
			$split = strpos($row->val, "\n") ? "\n" : '|';
			$val   = explode($split, $row->val);
			$vals  = array_merge($vals, $val);
		}

		$vals = array_unique($vals);

		return $vals;
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

		$in = TRUE;

		// --------------------------------------
		// Check if parameter is "not bla|bla"
		// --------------------------------------

		if (strtolower(substr($str, 0, 4)) == 'not ')
		{
			// Change $in var accordingly
			$in = FALSE;

			// Strip 'not ' from string
			$str = substr($str, 4);
		}

		// --------------------------------------
		// Return two values in an array
		// --------------------------------------

		return array(preg_split('/(&|\|)/', $str), $in);
	}

	// --------------------------------------------------------------------

}
