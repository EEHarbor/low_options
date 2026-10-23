<?php

namespace LowOptionsTests;

use Low\Options\FluxCapacitor\Conduit\StaticCache;

class ArchiveBridgeTest extends TestCase
{
    /**
     * Provide bridge vectors.
     *
     * @return array
     */
    public static function bridgeProvider(): array
    {
        return array(array('EE2'), array('EE3'), array('EE4'), array('EE5'), array('EE6'));
    }

    /**
     * Construct an actual archive bridge with the shipped module metadata.
     *
     * @param string $version
     * @return object
     */
    private function bridge($version)
    {
        $class = 'Low\\Options\\FluxCapacitor\\Bridge\\' . $version;
        return new $class(array('module' => 'low_options', 'module_name' => 'Low Options'));
    }

    /**
     * Verify runtime comparison helpers against the defined EE release.
     *
     * @dataProvider bridgeProvider
     * @param string $version
     * @return void
     */
    public function testVersionComparisons($version): void
    {
        $bridge = $this->bridge($version);
        self::assertSame('7', $bridge->getEEVersion());
        self::assertSame('7.5.0', $bridge->getEEVersion(false));
        self::assertTrue($bridge->ver_lt('8'));
        self::assertFalse($bridge->ver_lt('7'));
        self::assertTrue($bridge->ver_gt('6'));
        self::assertFalse($bridge->ver_gt('7'));
        self::assertTrue($bridge->ver_lte('7'));
        self::assertFalse($bridge->ver_lte('6'));
        self::assertTrue($bridge->ver_gte('7'));
        self::assertFalse($bridge->ver_gte('8'));
        self::assertFalse($bridge->is_ee2());
        self::assertFalse($bridge->is_ee3());
        self::assertFalse($bridge->is_ee4());
        self::assertFalse($bridge->is_ee5());
    }

    /**
     * Hydrate addon settings and retain per-request updates.
     *
     * @dataProvider bridgeProvider
     * @param string $version
     * @return void
     */
    public function testSettingsFallbackAndInMemoryUpdates($version): void
    {
        $bridge = $this->bridge($version);
        $settings = $bridge->getSettings(true);
        self::assertSame('Low Options', $settings['name']);
        self::assertSame('2.2.0', $bridge->getConfig('version'));
        self::assertSame($settings, (array) $bridge->getSettings());
        $bridge->setConfig('custom', array('zero' => 0));
        self::assertSame(array('zero' => 0), $bridge->getConfig('custom'));
        self::assertSame(array('zero' => 0), StaticCache::get('app_settings')['custom']);
    }

    /**
     * Store settings by site and choose the correct insert/update action.
     *
     * @dataProvider settingsWriteProvider
     * @param string $version
     * @param int $count
     * @return void
     */
    public function testSettingsDatabaseWrite($version, $count): void
    {
        $this->ee->db->tableExists = true;
        $this->ee->db->count = $count;
        $bridge = $this->bridge($version);
        $bridge->setConfig('custom', 'value');
        self::assertSame('value', $bridge->getConfig('custom'));
        if ($version === 'EE2') {
            // The clone must write by site without touching the original builder.
            $operation = $count ? 'update' : 'insert';
            self::assertContains(array($operation, array('low_options_settings', array('site_id' => 1, 'custom' => 'value'))), $this->ee->db->sharedTrace->calls);
            self::assertContains(array('where', array('site_id', 1)), $this->ee->db->sharedTrace->calls);
            self::assertSame(array(), $this->ee->db->calls);
            self::assertArrayHasKey('table_names', $this->ee->db->data_cache);
            return;
        }
        $operation = $count ? 'update' : 'insert';
        self::assertContains(array($operation, array('low_options_settings', array('site_id' => 1, 'custom' => 'value'))), $this->ee->db->calls);
        self::assertContains(array('where', array('site_id', 1)), $this->ee->db->calls);
    }

    /**
     * Provide settings write vectors.
     *
     * @return array
     */
    public static function settingsWriteProvider(): array
    {
        $data = array();
        foreach (array('EE2', 'EE3', 'EE4', 'EE5') as $version) {
            $data[] = array($version, 0);
            $data[] = array($version, 1);
        }
        return $data;
    }

    /**
     * Parse request pagination values and fall back to the first page.
     *
     * @dataProvider bridgeProvider
     * @param string $version
     * @return void
     */
    public function testPaginationAndOffset($version): void
    {
        $bridge = $this->bridge($version);
        self::assertSame(1, $bridge->getCurrentPage(array('per_page' => 10)));
        $this->ee->input->get['page'] = '3';
        self::assertEquals(3, $bridge->getCurrentPage(array('per_page' => 10)));
        $this->ee->input->get['per_page'] = '20';
        self::assertEquals($version === 'EE2' ? 3 : 20, $bridge->getCurrentPage(array('per_page' => 10)));
        self::assertSame(20, $bridge->getStartNum(array('current_page' => 3, 'per_page' => 10)));
        $options = array('total_rows' => 50, 'per_page' => 10, 'current_page' => 3, 'base_url' => '/items', 'page_links' => 5, 'page_query_string' => 'p');
        $this->ee->services['CP/Pagination'] = $this->ee->pagination;
        self::assertSame('PAGINATION', $bridge->pagination($options));
        if ($version === 'EE2') {
            self::assertSame(array('initialize', array(array_merge($options, array('cur_page' => 3)))), $this->ee->pagination->calls[0]);
            return;
        }
        self::assertSame(array(
            array('perPage', array(10)), array('currentPage', array(3)),
            array('displayPageLinks', array(5)), array('queryStringVariable', array('p')),
            array('render', array('/items')),
        ), $this->ee->pagination->calls);
        $bridge->pagination(array('total_rows' => 1, 'per_page' => 5, 'current_page' => 1, 'base_url' => '/'));
    }

    /**
     * Keep persistent and request caches distinct.
     *
     * @dataProvider bridgeProvider
     * @param string $version
     * @return void
     */
    public function testCacheRoutingAndDeletion($version): void
    {
        $bridge = $this->bridge($version);
        self::assertFalse($bridge->cache('unknown'));
        self::assertFalse($bridge->cache('get', 'missing', false, false));
        $bridge->cache('set', 'request', 0, false);
        self::assertSame(0, $bridge->cache('get', 'request', false, false));
        $bridge->cache('set', 'disk', array(1), true);
        self::assertSame(array(1), $bridge->cache('get', 'disk', false, true));
        $bridge->cache('delete', 'disk');
        self::assertFalse($bridge->cache('get', 'disk'));
        $bridge->cache('clear');
        if ($version === 'EE2') {
            self::assertArrayNotHasKey('low_options', $this->ee->session->cache);
            return;
        }
        self::assertSame(0, $this->ee->session->cache['low_options']['request']);
    }

    /**
     * Route normalized control panel URLs without losing extra variables.
     *
     * @dataProvider modernBridgeProvider
     * @param string $version
     * @return void
     */
    public function testModernControlPanelUrls($version): void
    {
        $bridge = $this->bridge($version);
        self::assertSame('addons/settings/low_options', $bridge->getBaseURL('/')->path);
        self::assertSame('addons/settings/low_options/method?extra=1', $bridge->getBaseURL('method', '?extra=1')->path);
        self::assertSame('publish/create/9', $bridge->cpURL('listing', 'create', array('channel_id' => 9))->path);
        $url = $bridge->cpURL('publish', 'edit', array('entry_id' => 17, 'extra' => 'retained'));
        self::assertSame('publish/edit/entry/17', $url->path);
        self::assertSame(array('extra' => 'retained'), $url->variables);
        self::assertSame('members', $bridge->cpURL('members')->path);
        self::assertSame('publish/edit', $bridge->cpURL('publish', 'edit')->path);
        self::assertSame('addons/settings/low_options/history', $bridge->moduleURL('history', array('page' => 2))->path);
    }

    /**
     * Provide modern bridge vectors.
     *
     * @return array
     */
    public static function modernBridgeProvider(): array
    {
        return array(array('EE3'), array('EE4'), array('EE5'), array('EE6'));
    }

    /**
     * Verify legacy control panel urls.
     *
     * @return void
     */
    public function testLegacyControlPanelUrls(): void
    {
        $bridge = $this->bridge('EE2');
        self::assertSame(BASE . '&amp;D=cp&amp;C=content_edit&amp;channel_id=3', $bridge->cpURL('listing', 'edit', array('filter_by_channel' => 3)));
        self::assertStringEndsWith('C=content_publish&amp;M=entry_form', $bridge->cpURL('publish', 'create'));
        self::assertStringEndsWith('C=content_publish&amp;M=entry_form', $bridge->cpURL('publish', 'edit'));
        self::assertStringEndsWith('C=content_publish&amp;M=other', $bridge->cpURL('publish', 'other'));
        self::assertStringEndsWith('C=members&amp;M=member_group_manager', $bridge->cpURL('members', 'groups'));
        self::assertStringEndsWith('C=members', $bridge->cpURL('members'));
        self::assertStringEndsWith('C=admin_content&amp;M=channel_add', $bridge->cpURL('channels', 'create'));
        self::assertStringEndsWith('C=admin_content', $bridge->cpURL('channels'));
        self::assertStringEndsWith('C=addons_modules', $bridge->cpURL('addons'));
        self::assertStringEndsWith('C=unknown', $bridge->cpURL('unknown'));
    }

    /**
     * Verify legacy base url is diagnostic free.
     *
     * @return void
     */
    public function testLegacyBaseUrlIsDiagnosticFree(): void
    {
        $bridge = $this->bridge('EE2');
        self::assertStringEndsWith('module=low_options&amp;D=cp', $bridge->getBaseURL('/'));
        self::assertStringContainsString('&amp;method=edit&amp;extra=1', $bridge->getBaseURL('edit', '&amp;extra=1'));
        self::assertStringContainsString('&amp;method=index', $bridge->moduleURL('index', null));
        self::assertStringEndsWith('&amp;page=2', $bridge->moduleURL('history', array('page' => 2)));
    }

    /**
     * Use configured cache paths, theme URLs and safe footer injection.
     *
     * @dataProvider bridgeProvider
     * @param string $version
     * @return void
     */
    public function testPathsAndFooter($version): void
    {
        $bridge = $this->bridge($version);
        self::assertSame($version === 'EE2' ? APPPATH . 'cache/' : PATH_CACHE, $bridge->getCachePath());
        $this->ee->config->items['cache_path'] = '/configured/';
        self::assertSame('/configured/', $bridge->getCachePath());
        self::assertSame('/themes/user/low_options/', $bridge->getAddonThemesDir());
        $bridge->javascript_to_page('var example = 1;');
        self::assertSame(array('<script type="text/javascript">var example = 1;</script>'), $bridge->getFooter());
    }
}
