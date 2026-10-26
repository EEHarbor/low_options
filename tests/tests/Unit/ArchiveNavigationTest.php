<?php

namespace LowOptionsTests;

require_once LOW_OPTIONS_ROOT . '/tests/Support/NativeProcess.php';

class ArchiveNavigationTest extends TestCase
{
    /**
     * Verify real installed-addon navigation with original archive source.
     *
     * @dataProvider navigationProvider
     * @param string $context
     * @param array $expected
     * @return void
     */
    public function testInstalledAddonNavigation($context, array $expected): void
    {
        list($exit, $stdout, $stderr) = NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/nav-probe.php', array($context));
        self::assertSame(0, $exit, $stderr . $stdout);
        self::assertSame('', $stderr);
        self::assertSame($expected, json_decode($stdout, true));
    }

    /**
     * Provide aliases, mapped/direct activity, deferred and empty contexts.
     *
     * @return array
     */
    public static function navigationProvider(): array
    {
        $home = array('title' => 'Home', 'active' => false, 'url' => 'addons/settings/low_options/index', 'buttons' => array());
        $history = array('title' => 'History', 'active' => true, 'url' => 'addons/settings/low_options/history', 'buttons' => array());
        $fullHistory = $history;
        $fullHistory['buttons'] = array(array('Add', 'addons/settings/low_options/new'));
        $group = array('title' => 'Group', 'active' => false, 'url' => null, 'buttons' => array());
        $external = array('title' => 'External', 'active' => false, 'url' => 'https://example.test', 'buttons' => array());
        $activeHome = $home;
        $activeHome['active'] = true;
        $inactiveHistory = $history;
        $inactiveHistory['active'] = false;
        $inactiveFullHistory = $fullHistory;
        $inactiveFullHistory['active'] = false;
        $license = array('title' => 'License', 'active' => false, 'url' => 'addons/settings/low_options/license', 'buttons' => array());
        $cases = array(
            array('full', array('items' => array($home, $fullHistory, $group, $external), 'active_title' => 'History', 'deferred' => false, 'breadcrumbs' => 1, 'default_toolbar' => 'Low Options', 'custom_toolbar' => array('Custom', 'addons/settings/low_options/history'), 'slash_url' => 'addons/settings/low_options/index', 'external_url' => 'https://example.test')),
            array('empty-alias', array('items' => array($activeHome, $inactiveHistory), 'active_title' => 'Home', 'deferred' => false, 'breadcrumbs' => 1)),
            array('existing-map', array('items' => array($home, $history), 'active_title' => 'History', 'deferred' => false, 'breadcrumbs' => 1)),
            array('direct', array('items' => array($history, $license), 'active_title' => 'History', 'deferred' => false, 'breadcrumbs' => 1)),
            array('defer', array('items' => array($history, $license), 'active_title' => 'History', 'deferred' => true, 'breadcrumbs' => 1)),
            array('inactive', array('items' => array($home, $inactiveFullHistory, $group, $external), 'active_title' => null, 'deferred' => false, 'breadcrumbs' => 0)),
            array('empty', array('items' => array(), 'active_title' => null, 'deferred' => false, 'breadcrumbs' => 0)),
        );
        foreach ($cases as $index => $case) {
            $cases[$index][1]['active_reference'] = $case[1]['active_title'] === null ? null : true;
        }
        return $cases;
    }
}
