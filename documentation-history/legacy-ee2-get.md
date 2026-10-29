# Historical EE2 tag syntax

The `origin/ee2` README documented a compatibility tag for ExpressionEngine 2.4 and earlier:

```text
{exp:low_options:get field="channel_field_short_name"}
    {if option:group != ''}<optgroup label="{option:group}">{/if}
        {options}<option value="{option:value}">{option:label}</option>{/options}
    {if option:group != ''}</optgroup>{/if}
{/exp:low_options:get}
```

This historical procedure is preserved outside EEHarbor’s active documentation lookup paths. The current guide uses the field’s short name as the third tag part. Presence of a legacy method in the current source does not establish a tested current workflow.
