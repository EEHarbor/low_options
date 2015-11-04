# Low Options for ExpressionEngine

Low Options displays the list items or options for a given channel field. These options are the values given in the settings of the field (eg: dropdown fields). If no such options are given (eg: regular text input fields), a list of unique values of that field will be generated based on the given parameters.

Requires **EE 3.0.0+** and is especially useful in combination with [Low Search](http://gotolow/addons/low-search). The EE2 compatible version [is available here](https://github.com/low/low_options/tree/ee2).

## Installation

- [Download](https://github.com/low/low_options/archive/master.zip) and unzip;
- Copy the `low_options` folder to your `system/user/addons` directory;
- In your Control Panel, go to the Add-on Manager and install Low Options;
- All set!

## Tags

Use the channel field’s short name as the third tag part to list the options.

	{exp:low_options:channel_field_short_name}
	  {if option:group != ''}<optgroup label="{option:group}">{/if}
	  {options}<option value="{option:value}">{option:label}</option>{/options}
	  {if option:group != ''}</optgroup>{/if}
	{/exp:low_options:channel_field_short_name}

## Parameters

- `ignore=`: Pipe-separated list of *values* to ignore
- `show_empty=`: Set to `no` to only show values that have been assigned to entries
- `channel=`: Use in combination with `show_empty="no"` to filter assigned entries
- `status=`: Use in combination with `show_empty="no"` to filter assigned entries
- `category=`: Use in combination with `show_empty="no"` to filter assigned entries
- `show_future_entries=`: Use in combination with `show_empty="no"` to filter assigned entries
- `show_expired=`: Use in combination with `show_empty="no"` to filter assigned entries
- `sort=""`: Only applies to text fields: set to `desc` to display generated options in reverse order