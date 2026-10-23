<?php

namespace LowOptionsTests;

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
        $command = array(PHP_BINARY, '-d', 'error_reporting=-1', '-d', 'display_errors=1', LOW_OPTIONS_ROOT . '/tests/scripts/nav-probe.php', $context);
        $process = proc_open($command, array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes, LOW_OPTIONS_ROOT);
        if (!is_resource($process)) {
            throw new \RuntimeException('Could not start installed-addon navigation probe.');
        }
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $stderr . $stdout);
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
