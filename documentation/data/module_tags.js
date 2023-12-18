window.doc_page = {
    addon: 'Low Options',
    title: 'Tags',
    sections: [
        {
            title: '',
            type: 'tagtoc',
            desc: 'Low Options has the following front-end tags: ',
        },
        {
            title: '',
            type: 'tags',
            desc: ''
        },
    ],
    tags: [
        {
            tag: '{exp:low_options:channel_field_short_name}',
            shortname: 'exp:low_options:channel_field_short_name',
            summary: "The main single tag: Use the channel field’s short name as the third tag part to list the options.",
            desc: "Use the channel field’s short name as the third tag part to list the options.",
            sections: [
                {
                    type: 'params',
                    title: 'Tag Parameters',
                    desc: '',
                    items: [
                        {
                            item: 'ignore=',
                            desc: 'Pipe-separated list of values to ignore',
                            type: '',
                            accepts: '',
                            default: '',
                            required: false,
                            added: '',
                            examples: [
                                {
                                    tag_example: `
{exp:low_options:channel_field_short_name ignore="Value1|Value2"}
    {if option:group != ''}<optgroup label="{option:group}">{/if}
        {options}<option value="{option:value}">{option:label}</option>{/options}
    {if option:group != ''}</optgroup>{/if}
{/exp:low_options:channel_field_short_name}`,
                                    outputs: ``
                                 }
                             ]
                        },
                        {
                            item: 'show_empty',
                            desc: 'Set to no to only show values that have been assigned to entries',
                            type: 'yes/no',
                            accepts: '',
                            default: '',
                            required: false,
                            added: '',
                            examples: [
                                {
                                    tag_example: `{exp:low_options:channel_field_short_name show_empty="no"}`,
                                    outputs: ``
                                 }
                             ]
                        },
                        {
                            item: 'channel',
                            desc: 'Use in combination with show_empty="no" to filter assigned entries',
                            type: '',
                            accepts: '',
                            default: '',
                            required: false,
                            added: '',
                            examples: [
                                {
                                    tag_example: `{exp:low_options:channel_field_short_name channel="my_channel"}`,
                                    outputs: ``
                                 }
                             ]
                        },
                        {
                            item: 'status',
                            desc: 'Use in combination with show_empty="no" to filter assigned entries',
                            type: '',
                            accepts: '',
                            default: '',
                            required: false,
                            added: '',
                            examples: [
                                {
                                    tag_example: `{exp:low_options:channel_field_short_name status="my_status"}`,
                                    outputs: ``
                                 }
                             ]
                        },
                        {
                            item: 'category',
                            desc: 'Use in combination with show_empty="no" to filter assigned entries',
                            type: '',
                            accepts: '',
                            default: '',
                            required: false,
                            added: '',
                            examples: [
                                {
                                    tag_example: `{exp:low_options:channel_field_short_name category="my_category"}`,
                                    outputs: ``
                                 }
                             ]
                        },
                        {
                            item: 'show_future_entries',
                            desc: 'Use in combination with show_empty="no" to filter assigned entries',
                            type: 'yes/no or true',
                            accepts: '',
                            default: '',
                            required: false,
                            added: '',
                            examples: [
                                {
                                    tag_example: `{exp:low_options:channel_field_short_name show_future_entries="true"}`,
                                    outputs: ``
                                 }
                             ]
                        },
                        {
                            item: 'show_expired',
                            desc: 'Use in combination with show_empty="no" to filter assigned entries',
                            type: 'yes/no or true',
                            accepts: '',
                            default: '',
                            required: false,
                            added: '',
                            examples: [
                                {
                                    tag_example: `{exp:low_options:channel_field_short_name show_expired="true"}`,
                                    outputs: ``
                                 }
                             ]
                        },
                        {
                            item: 'sort',
                            desc: 'Only applies to text fields: set to desc to display generated options in reverse order',
                            type: 'desc',
                            accepts: 'desc',
                            default: '',
                            required: false,
                            added: '',
                            examples: [
                                {
                                    tag_example: `{exp:low_options:channel_field_short_name sort="desc"}`,
                                    outputs: ``
                                 }
                             ]
                        },

                    ]
                }
            ]
        },
        
    ]
};