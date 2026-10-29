window.doc_page = {
    "addon": "Low Options",
    "title": "Tag Reference",
    "desc": "<p>Use a channel field’s short name as the third part of the tag pair. Replace `channel_field_short_name` in every example with the actual field name. See <a href=\"/low-options/documentation/installation\">Installation</a> for setup.</p>",
    "terms": [],
    "sections": [
        {
            "type": "tagtoc"
        },
        {
            "type": "tags"
        }
    ],
    "tags": [
        {
            "tag": "{exp:low_options:channel_field_short_name}",
            "shortname": "exp:low_options:channel_field_short_name",
            "desc": "List a field’s configured options, or generate unique values from its entries when no options are configured. This is a tag pair; {options} loops through each group’s values.",
            "sections": [
                {
                    "type": "examples",
                    "title": "Usage",
                    "items": [
                        {
                            "item": "Options and groups",
                            "desc": "The existing option variables provide the group name, stored value and display label. The wrapper keeps grouped options in `&lt;optgroup&gt;` elements and places ungrouped choices directly in the select.",
                            "examples": [
                                {
                                    "tag_example": "<label for=\"field_options\">Choose an option</label>\n<select id=\"field_options\" name=\"field_options\">\n    {exp:low_options:channel_field_short_name}\n        {if option:group != ''}<optgroup label=\"{option:group}\">{/if}\n            {options}<option value=\"{option:value}\">{option:label}</option>{/options}\n        {if option:group != ''}</optgroup>{/if}\n    {/exp:low_options:channel_field_short_name}\n</select>"
                                }
                            ]
                        }
                    ]
                },
                {
                    "type": "params",
                    "title": "Parameters",
                    "desc": "Entry filters select the source entries when a field’s option list is generated. For configured option lists, use `show_empty=\"no\"` to filter by assigned entry values.<p>Options are cached by field name within the request. For a field without configured options, later tags reuse the first generated set: changing filters or sort does not rebuild it. `show_empty=\"no\"` can filter that cached set further, but cannot restore values excluded by the first call. Use consistent settings when listing the same field more than once.</p><p>With `show_empty=\"no\"`, flat options with falsey labels, including the string `0`, are also removed by the current filter. Check zero-labelled choices before using the filtered list in a form.</p>",
                    "items": [
                        {
                            "item": "ignore",
                            "desc": "A pipe-separated list of option values to leave out of the output. Match the value, which can differ from its display label.",
                            "type": "string",
                            "examples": [
                                {
                                    "tag_example": "<label for=\"field_options\">Choose an option</label>\n<select id=\"field_options\" name=\"field_options\">\n    {exp:low_options:channel_field_short_name ignore=\"Value1|Value2\"}\n        {if option:group != ''}<optgroup label=\"{option:group}\">{/if}\n            {options}<option value=\"{option:value}\">{option:label}</option>{/options}\n        {if option:group != ''}</optgroup>{/if}\n    {/exp:low_options:channel_field_short_name}\n</select>"
                                }
                            ]
                        },
                        {
                            "item": "show_empty",
                            "desc": "For fields with configured options, set to `no` to keep only choices used by matching entries. Otherwise all configured options are listed. Fields without configured options derive their initial values from matching entries; see the same-field cache limitation above.",
                            "type": "string",
                            "default": "all configured options",
                            "accepts": "no to filter by entries",
                            "examples": [
                                {
                                    "tag_example": "<label for=\"field_options\">Choose an option</label>\n<select id=\"field_options\" name=\"field_options\">\n    {exp:low_options:channel_field_short_name show_empty=\"no\"}\n        {if option:group != ''}<optgroup label=\"{option:group}\">{/if}\n            {options}<option value=\"{option:value}\">{option:label}</option>{/options}\n        {if option:group != ''}</optgroup>{/if}\n    {/exp:low_options:channel_field_short_name}\n</select>"
                                }
                            ]
                        },
                        {
                            "item": "channel",
                            "desc": "Filter the entries used to find assigned values by channel short name. This applies with `show_empty=\"no\"` or when the field’s list is first generated from entries.",
                            "type": "string",
                            "examples": [
                                {
                                    "tag_example": "<label for=\"field_options\">Choose an option</label>\n<select id=\"field_options\" name=\"field_options\">\n    {exp:low_options:channel_field_short_name show_empty=\"no\" channel=\"articles\"}\n        {if option:group != ''}<optgroup label=\"{option:group}\">{/if}\n            {options}<option value=\"{option:value}\">{option:label}</option>{/options}\n        {if option:group != ''}</optgroup>{/if}\n    {/exp:low_options:channel_field_short_name}\n</select>"
                                }
                            ]
                        },
                        {
                            "item": "status",
                            "desc": "Filter the entries used to find assigned values by entry status. This applies with `show_empty=\"no\"` or when the field’s list is first generated from entries.",
                            "type": "string",
                            "default": "open",
                            "examples": [
                                {
                                    "tag_example": "<label for=\"field_options\">Choose an option</label>\n<select id=\"field_options\" name=\"field_options\">\n    {exp:low_options:channel_field_short_name show_empty=\"no\" status=\"open\"}\n        {if option:group != ''}<optgroup label=\"{option:group}\">{/if}\n            {options}<option value=\"{option:value}\">{option:label}</option>{/options}\n        {if option:group != ''}</optgroup>{/if}\n    {/exp:low_options:channel_field_short_name}\n</select>"
                                }
                            ]
                        },
                        {
                            "item": "category",
                            "desc": "Filter the entries used to find assigned values by category ID. Use an actual numeric ID. This applies with `show_empty=\"no\"` or when the field’s list is first generated from entries.",
                            "type": "string",
                            "examples": [
                                {
                                    "tag_example": "<label for=\"field_options\">Choose an option</label>\n<select id=\"field_options\" name=\"field_options\">\n    {exp:low_options:channel_field_short_name show_empty=\"no\" category=\"3\"}\n        {if option:group != ''}<optgroup label=\"{option:group}\">{/if}\n            {options}<option value=\"{option:value}\">{option:label}</option>{/options}\n        {if option:group != ''}</optgroup>{/if}\n    {/exp:low_options:channel_field_short_name}\n</select>"
                                }
                            ]
                        },
                        {
                            "item": "show_future_entries",
                            "desc": "Set to `yes` to include future-dated entries when finding assigned values. The implementation checks specifically for `yes`; `true` does not enable this. This applies with `show_empty=\"no\"` or when the field’s list is first generated from entries.",
                            "type": "string",
                            "default": "no",
                            "accepts": "yes, no",
                            "examples": [
                                {
                                    "tag_example": "<label for=\"field_options\">Choose an option</label>\n<select id=\"field_options\" name=\"field_options\">\n    {exp:low_options:channel_field_short_name show_empty=\"no\" show_future_entries=\"yes\"}\n        {if option:group != ''}<optgroup label=\"{option:group}\">{/if}\n            {options}<option value=\"{option:value}\">{option:label}</option>{/options}\n        {if option:group != ''}</optgroup>{/if}\n    {/exp:low_options:channel_field_short_name}\n</select>"
                                }
                            ]
                        },
                        {
                            "item": "show_expired",
                            "desc": "Set to `yes` to include expired entries when finding assigned values. The implementation checks specifically for `yes`; `true` does not enable this. This applies with `show_empty=\"no\"` or when the field’s list is first generated from entries.",
                            "type": "string",
                            "default": "no",
                            "accepts": "yes, no",
                            "examples": [
                                {
                                    "tag_example": "<label for=\"field_options\">Choose an option</label>\n<select id=\"field_options\" name=\"field_options\">\n    {exp:low_options:channel_field_short_name show_empty=\"no\" show_expired=\"yes\"}\n        {if option:group != ''}<optgroup label=\"{option:group}\">{/if}\n            {options}<option value=\"{option:value}\">{option:label}</option>{/options}\n        {if option:group != ''}</optgroup>{/if}\n    {/exp:low_options:channel_field_short_name}\n</select>"
                                }
                            ]
                        },
                        {
                            "item": "sort",
                            "desc": "For fields without configured options, unique entry values are naturally sorted without regard to case. Set `desc` to reverse this generated list. Configured option lists keep their stored order.",
                            "type": "string",
                            "default": "ascending generated values",
                            "accepts": "desc to reverse generated values",
                            "examples": [
                                {
                                    "tag_example": "<label for=\"field_options\">Choose an option</label>\n<select id=\"field_options\" name=\"field_options\">\n    {exp:low_options:channel_field_short_name sort=\"desc\"}\n        {if option:group != ''}<optgroup label=\"{option:group}\">{/if}\n            {options}<option value=\"{option:value}\">{option:label}</option>{/options}\n        {if option:group != ''}</optgroup>{/if}\n    {/exp:low_options:channel_field_short_name}\n</select>"
                                }
                            ]
                        }
                    ]
                }
            ]
        }
    ]
};
