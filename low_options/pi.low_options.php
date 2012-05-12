<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// Provide info to EE
$plugin_info = array(
	'pi_name'        => 'Low Options',
	'pi_version'     => '0.0.3',
	'pi_author'      => 'Lodewijk Schutte ~ Low',
	'pi_author_url'  => '#',
	'pi_description' => 'Get options from select field.',
	'pi_usage'       => Low_options::usage()
);

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
	 * EE Instance
	 *
	 * @access      private
	 * @var         object
	 */
	private $EE;

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
	 * Legacy constructor
	 *
	 * @access      public
	 * @return      string
	 */
	public function Low_options()
	{
		$this->__construct();
	}

	/**
	 * Constructor: sets EE instance
	 *
	 * @access      public
	 * @return      string
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
	}

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
		if ($field = $this->EE->TMPL->fetch_param('field'))
		{
			$this->_set_field_options($field);
		}

		return $this->_parse_field_options();
	}

	/**
	 * Get field options from cache or DB
	 *
	 * @access      public
	 * @param       string
	 * @return      void
	 */
	private function _set_field_options($field_name)
	{
		// Serves as local cache
		static $fields = array();

		// If this is an unknown field name, get it from the DB
		if ( ! isset($fields[$field_name]))
		{
			// Initiate options
			$options = array();

			// Get stuff from DB
			$query = $this->EE->db->select('field_list_items, field_settings')
			       ->from('channel_fields')
			       ->where('field_name', $field_name)
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
		}

		// Set the options
		$this->field_options = $fields[$field_name];
	}

	/**
	 * Parse options
	 *
	 * @access      public
	 * @return      string
	 */
	private function _parse_field_options()
	{
		$data = array();

		foreach ($this->field_options AS $key => $val)
		{
			$options = array();

			if (is_array($val))
			{
				foreach ($val AS $k => $v)
				{
					$options[] = $this->_option($k, $v);
				}
				$group_name = $key;
			}
			else
			{
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
			? $this->EE->TMPL->parse_variables($this->EE->TMPL->tagdata, $data)
			: $this->EE->TMPL->no_results();

		return $this->return_data;
	}

	/**
	* Return an option row in array form
	*
	* @access      public
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
	* Usage
	*
	* @access      public
	* @return      string
	*/
	public function usage()
	{
		return <<<EOF
	{exp:low_options:my_channel_field}
		{options}
			<option value="{option:value}">{option:label}</option>
		{/options}
	{/exp:low_options:my_channel_field}
EOF;
	}
}