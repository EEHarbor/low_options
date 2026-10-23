<?php

namespace LowOptionsTests;

use Low\Options\FluxCapacitor\Base\Mcp;

class ArchiveMcpTest extends TestCase
{
    /**
     * Verify control panel constructor and index redirect.
     *
     * @return void
     */
    public function testControlPanelConstructorAndIndexRedirect(): void
    {
        $mcp = new Mcp();
        self::assertSame('2.2.0', $mcp->version);
        self::assertNull($mcp->nav);
        $this->expectException(Redirect::class);
        $this->expectExceptionMessage('addons/settings/low_options/license');
        $mcp->index();
    }

    /**
     * Render license data including ignored-site masking.
     *
     * @dataProvider licenseViewProvider
     * @param array $record
     * @param string $key
     * @param mixed $ignore
     * @return void
     */
    public function testLicenseView(array $record, $key, $ignore): void
    {
        $mcp = new Mcp();
        $this->ee->db->record = $record;
        $view = new ViewService();
        $this->ee->services['View'] = $view;
        $result = $mcp->license();
        self::assertSame('VIEW', $result['body']);
        self::assertSame('low_options:license', $view->template);
        self::assertSame($key, $view->variables['license_key']);
        self::assertSame($ignore, $view->variables['ignore_site']);
        self::assertSame('7', $view->variables['ee_ver']);
        self::assertSame('addons/settings/low_options/save_license', $view->variables['action_url']->path);
        self::assertContains(array('where', array('site_id', 1)), $this->ee->db->calls);
        self::assertContains(array('where', array('addon', 'Low\\Options')), $this->ee->db->calls);
    }

    /**
     * Provide license view vectors.
     *
     * @return array
     */
    public static function licenseViewProvider(): array
    {
        return array(array(array(), '', false), array(array('license_key' => 'valid-key', 'ignore_site' => '0'), 'valid-key', 0), array(array('license_key' => 'ignored', 'ignore_site' => '1'), '', 1));
    }

    /**
     * Verify modern view supports inline content and explicit variables.
     *
     * @return void
     */
    public function testModernViewSupportsInlineContentAndExplicitVariables(): void
    {
        $mcp = new Mcp();
        $view = new ViewService();
        $this->ee->services['View'] = $view;
        $result = $mcp->flux->view('unused', array('base_url' => '/custom', 'cp_page_title' => 'Explicit'), true, '<p>inline</p>');
        self::assertSame('VIEW', $result['body']);
        self::assertSame('<p>inline</p>', $view->template);
        self::assertSame('/custom', $view->variables['base_url']);
        self::assertSame('Explicit', $view->variables['cp_page_title']);
    }

    /**
     * Verify updater installs configured module and clears database cache.
     *
     * @return void
     */
    public function testUpdaterInstallsConfiguredModuleAndClearsDatabaseCache(): void
    {
        $this->ee->db->tableExists = true;
        $updater = new \Low\Options\FluxCapacitor\Base\Upd();
        self::assertSame('2.2.0', $updater->version);
        self::assertTrue($updater->install());
        self::assertSame(array('db'), $this->ee->functions->cleared);
        self::assertContains(array('insert', array('modules', array('module_name' => get_class($updater), 'module_version' => '2.2.0', 'has_cp_backend' => 'y', 'has_publish_fields' => 'n'))), $this->ee->db->calls);
    }

    /**
     * Verify updater handles addon without optional extension diagnostics.
     *
     * @return void
     */
    public function testUpdaterHandlesAddonWithoutOptionalExtensionDiagnostics(): void
    {
        $this->ee->db->tableExists = true;
        $updater = new \Low\Options\FluxCapacitor\Base\Upd();
        self::assertTrue($updater->update('1.0'));
    }
}
