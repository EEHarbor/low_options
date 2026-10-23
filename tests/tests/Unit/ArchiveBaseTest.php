<?php

namespace LowOptionsTests;

use Low\Options\FluxCapacitor\Base\Ext;
use Low\Options\FluxCapacitor\Base\Mod;
use Low\Options\FluxCapacitor\Base\Pi;
use Low\Options\FluxCapacitor\Base\Tab;
use Low\Options\FluxCapacitor\Base\Upd;

class ConcreteFieldtype extends \Low\Options\FluxCapacitor\Base\Ft
{
}

class ArchiveBaseTest extends TestCase
{
    /**
     * Verify base constructors read addon metadata.
     *
     * @return void
     */
    public function testBaseConstructorsReadAddonMetadata(): void
    {
        foreach (array(new Ext(), new Mod(), new Pi(), new Tab(), new ConcreteFieldtype()) as $base) {
            self::assertSame('2.2.0', $base->version);
            self::assertSame('low_options', $base->flux->shortname);
        }
    }

    /**
     * Verify static plugin metadata is callable.
     *
     * @return void
     */
    public function testStaticPluginMetadataIsCallable(): void
    {
        $info = Pi::info();
        self::assertIsArray($info);
        self::assertSame('Low Options', $info['pi_name']);
    }

    /**
     * Trim HTML spaces, retain explicit trim opt-out and apply fallbacks.
     *
     * @dataProvider moduleParameterProvider
     * @param mixed $value
     * @param bool $trim
     * @param mixed $expected
     * @return void
     */
    public function testModuleParameterFallback($value, $trim, $expected): void
    {
        $module = new Mod();
        $this->ee->TMPL->params['parameter'] = $value;
        self::assertSame($expected, $this->invoke($module, 'getParameter', array('parameter', 'fallback', $trim)));
    }

    /**
     * Provide module parameter vectors.
     *
     * @return array
     */
    public static function moduleParameterProvider(): array
    {
        return array(
            array(' &nbsp;hello&#32; ', true, 'hello'),
            array(' keep ', false, ' keep '),
            array(false, true, 'fallback'),
            array('0', false, 'fallback'),
            array('', true, 'fallback'),
        );
    }

    /**
     * Verify tab aliases and entry variables.
     *
     * @return void
     */
    public function testTabAliasesAndEntryVariables(): void
    {
        $tab = new Tab();
        self::assertSame(array(), $tab->display(3, 17));
        self::assertSame(array(), $tab->publish_tabs(3));
        self::assertTrue($tab->save(null, array()));
        self::assertTrue($tab->publish_data_db(array()));
        $entry = (object) array('entry_id' => 17, 'channel_id' => 3, 'site_id' => 1);
        self::assertSame((array) $entry, $tab->getEntryVariables($entry, array()));
        self::assertSame((array) $entry, $tab->getEntryVariables(null, array('entry_id' => 17, 'meta' => array('channel_id' => 3, 'site_id' => 1))));
        self::assertSame(array('entry_id' => 0, 'channel_id' => 0, 'site_id' => 0), $tab->getEntryVariables(null, array()));
    }

    /**
     * Verify extension registration is idempotent and preserves settings.
     *
     * @return void
     */
    public function testExtensionRegistrationIsIdempotentAndPreservesSettings(): void
    {
        $extension = new Ext();
        $extension->settings = array('custom' => 'retained');
        self::assertTrue($extension->registerExtension('handler', 'hook', 42, 'n'));
        self::assertContains(array('insert', array('extensions', array('class' => get_class($extension), 'method' => 'handler', 'hook' => 'hook', 'settings' => serialize(array('custom' => 'retained')), 'priority' => 42, 'version' => '2.2.0', 'enabled' => 'n'))), $this->ee->db->calls);
        $this->ee->db->num_rows = 1;
        $before = count($this->ee->db->calls);
        self::assertTrue($extension->registerExtension('handler', 'hook'));
        self::assertCount($before + 1, $this->ee->db->calls);
    }

    /**
     * Verify extension defaults and lifecycle aliases.
     *
     * @return void
     */
    public function testExtensionDefaultsAndLifecycleAliases(): void
    {
        $extension = new Ext();
        $extension->settings = 'invalid legacy settings';
        $extension->hooks = array('first', 'second');
        self::assertTrue($extension->activate_extension());
        self::assertSame(array(), $extension->settings);
        self::assertTrue($extension->update_extension('1.0'));
        self::assertTrue($extension->disable_extension());
        self::assertTrue($extension->cp_custom_menu(null));
        self::assertTrue($this->invoke($extension, 'update_version'));
        self::assertTrue($this->invoke($extension, 'unregisterExtension', array('handler')));
        self::assertTrue($this->invoke($extension, 'unregister_extension', array('handler')));
        self::assertContains(array('delete', array('extensions')), $this->ee->db->calls);
        self::assertContains(array('delete', array('menu_items')), $this->ee->db->calls);
    }

    /**
     * Verify legacy registration alias preserves explicit arguments.
     *
     * @return void
     */
    public function testLegacyRegistrationAliasPreservesExplicitArguments(): void
    {
        $extension = new Ext();
        $this->invoke($extension, 'register_extension', array('handler', 'custom_hook', 20, 'n'));
        $inserts = array_values(array_filter($this->ee->db->calls, function ($call) { return $call[0] === 'insert'; }));
        self::assertSame('custom_hook', $inserts[0][1][1]['hook']);
        self::assertSame(20, $inserts[0][1][1]['priority']);
        self::assertSame('n', $inserts[0][1][1]['enabled']);
    }

    /**
     * Verify fieldtype content types settings rows and aliases.
     *
     * @return void
     */
    public function testFieldtypeContentTypesSettingsRowsAndAliases(): void
    {
        $fieldtype = new ConcreteFieldtype();
        self::assertTrue($fieldtype->update('1.0'));
        self::assertTrue($fieldtype->accepts_content_type('channel'));
        self::assertFalse($fieldtype->accepts_content_type('member'));
        $fieldtype->_display_settings_add_row('Title', '<input>', 'Description', true);
        $rows = $fieldtype->_package_display_settings('options', 'group', 'Label');
        self::assertSame('Label', $rows['options']['label']);
        self::assertSame('group', $rows['options']['group']);
        self::assertSame(array(array(
            'title' => 'Title', 'desc' => 'Description', 'wide' => true,
            'fields' => array(array('type' => 'html', 'content' => '<input>')),
        )), $rows['options']['settings']);
        self::assertArrayHasKey('field_options_low_options', $fieldtype->displaySettingsReturn());
    }

    /**
     * Verify fieldtype channel resolution uses settings then entry.
     *
     * @return void
     */
    public function testFieldtypeChannelResolutionUsesSettingsThenEntry(): void
    {
        $fieldtype = new ConcreteFieldtype();
        $fieldtype->settings['field_channel_id'] = 3;
        self::assertSame(3, $fieldtype->getChannelId());
        unset($fieldtype->settings['field_channel_id']);
        $this->ee->input->get['channel_id'] = 8;
        self::assertSame(8, $fieldtype->getChannelId());
        $this->ee->input->get['channel_id'] = false;
        $fieldtype->entryId = 17;
        $this->ee->model->entries->first = (object) array('channel_id' => 9);
        self::assertSame(9, $fieldtype->getChannelId());
    }

    /**
     * Verify updater lifecycle preserves module and permission records.
     *
     * @return void
     */
    public function testUpdaterLifecyclePreservesModuleAndPermissionRecords(): void
    {
        $updater = (new \ReflectionClass(Upd::class))->newInstanceWithoutConstructor();
        $updater->version = '2.2.0';
        $updater->module_name = 'Low_options';
        $updater->ext_settings = 'y';
        self::assertTrue($updater->install());
        self::assertSame(array(), $this->ee->db->calls);
        self::assertFalse($updater->update(''));
        self::assertFalse($updater->update('2.2.0'));
        $updater->flux = new \Low\Options\FluxCapacitor\FluxCapacitor();
        $this->ee->db->record = array('module_id' => 6);
        self::assertTrue($updater->uninstall());
        self::assertContains(array('delete', array('module_member_roles')), $this->ee->db->calls);
        self::assertContains(array('delete', array('modules')), $this->ee->db->calls);
        self::assertContains(array('delete', array('actions')), $this->ee->db->calls);
        self::assertContains(array('delete', array('extensions')), $this->ee->db->calls);
    }
}
