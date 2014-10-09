# Low Options plugin for ExpressionEngine

Low Options displays the list items or options for a given channel field. These options are the values given in the settings of the field (eg: dropdown fields). If no such options are given (eg: regular text input fields), a list of unique values of that field will be generated based on the given parameters.

## Installation

- [Download](https://github.com/low/low_options/zipball/master) and unzip;
- Copy the `low_options` folder to your `system/expressionengine/third_party` directory.

## Usage

### ExpressionEngine 2.5 and up

	{exp:low_options:channel_field_short_name}
	  {if option:group != ''}<optgroup label="{option:group}">{/if}
	  {options}<option value="{option:value}">{option:label}</option>{/options}
	  {if option:group != ''}</optgroup>{/if}
	{/exp:low_options:channel_field_short_name}

### ExpressionEngine 2.4 and down

	{exp:low_options:get field="channel_field_short_name"}
	  {if option:group != ''}<optgroup label="{option:group}">{/if}
	  {options}<option value="{option:value}">{option:label}</option>{/options}
	  {if option:group != ''}</optgroup>{/if}
	{/exp:low_options:get}

## Parameters

- `ignore=""` : Pipe-separated list of *values* to ignore
- `show_empty=""` : Set to `no` to only show values that have been assigned to entries
- `channel=""` : Use in combination with `show_empty="no"` to filter assigned entries
- `status=""` : Use in combination with `show_empty="no"` to filter assigned entries
- `category=""` : Use in combination with `show_empty="no"` to filter assigned entries
- `show_future_entries=""` : Use in combination with `show_empty="no"` to filter assigned entries
- `show_expired=""` : Use in combination with `show_empty="no"` to filter assigned entries
- `sort=""` : Only applies to text fields: set to `desc` to display generated options in reverse order