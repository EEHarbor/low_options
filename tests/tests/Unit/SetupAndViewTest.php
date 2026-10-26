<?php

namespace LowOptionsTests;

class InlineAlert
{
    /**
     * Render an identifiable inline-alert marker.
     *
     * @return string
     */
    public function getAllInlines()
    {
        return '<span>INLINE ALERTS</span>';
    }
}

class SetupAndViewTest extends TestCase
{
    /**
     * Verify addon setup matches shipped manifest.
     *
     * @return void
     */
    public function testAddonSetupMatchesShippedManifest(): void
    {
        $setup = require LOW_OPTIONS_ROOT . '/low_options/addon.setup.php';
        $manifest = json_decode(file_get_contents(LOW_OPTIONS_ROOT . '/low_options/addon.json'), true);
        foreach (array('name', 'description', 'version', 'namespace') as $key) {
            self::assertSame($manifest[$key], $setup[$key]);
        }
        self::assertSame('EEHarbor', $setup['author']);
        self::assertFalse($setup['settings_exist']);
    }

    /**
     * Render both shipped views across EE versions and ignored-site keys.
     *
     * @dataProvider viewProvider
     * @param string $source
     * @param int $version
     * @param string $license
     * @param string $expected
     * @return void
     */
    public function testLicenseViews($source, $version, $license, $expected): void
    {
        $this->ee->services['CP/Alert'] = new InlineAlert();
        $ee_ver = $version;
        $action_url = '/save-license';
        $license_key = $license;
        $ignore_site = strpos($license, 'ignore-site-') !== false;
        ob_start();
        try {
            include $source;
            $html = ob_get_contents();
        } finally {
            ob_end_clean();
        }
        self::assertStringContainsString('class="box ee' . $version, $html);
        self::assertStringContainsString('License Key', $html);
        self::assertStringContainsString('Save License Key', $html);
        self::assertContains(array('input', 'license_key', $expected), $this->ee->formCalls);
        self::assertContains(array('checkbox', 'ignore_site', '1', $ignore_site), $this->ee->formCalls);
        self::assertContains(array('open', '/save-license', array('class' => 'settings panel-body')), $this->ee->formCalls);
        if ($version > 2) {
            self::assertStringContainsString('INLINE ALERTS', $html);
            return;
        }
        self::assertStringNotContainsString('INLINE ALERTS', $html);
    }

    /**
     * Provide view vectors.
     *
     * @return array
     */
    public static function viewProvider(): array
    {
        $data = array();
        foreach (array(LOW_OPTIONS_ROOT . '/low_options/views/license.php', 'phar://' . LOW_OPTIONS_ROOT . '/low_options/low_options.phar/Views/license.php') as $source) {
            foreach (array(2, 7) as $version) {
                $data[] = array($source, $version, 'test-license', 'test-license');
                $data[] = array($source, $version, 'low-options-ignore-site-1', '');
            }
        }
        return $data;
    }
}
