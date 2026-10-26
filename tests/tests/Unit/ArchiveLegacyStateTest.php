<?php

namespace LowOptionsTests;

require_once LOW_OPTIONS_ROOT . '/tests/Support/NativeProcess.php';
require_once LOW_OPTIONS_ROOT . '/tests/Support/LegacyState.php';

class ArchiveLegacyStateTest extends TestCase
{
    /**
     * Execute the original bridge with independent historical constants.
     *
     * @param array $options
     * @return array
     */
    private function native(array $options)
    {
        $directives = array();
        $extension = getenv('LOW_OPTIONS_LEGACY_XDEBUG');
        if ($extension) {
            $directives = array('zend_extension=' . $extension, 'xdebug.mode=coverage');
        }
        $result = NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/legacy-state-probe.php', array(json_encode($options, JSON_THROW_ON_ERROR)), $directives);
        self::assertSame(0, $result[0], $result[1] . $result[2]);
        self::assertSame('', $result[2]);
        return json_decode($result[1], true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Bind every mutation snapshot to its live owning builder.
     *
     * @return void
     */
    public function testDatabaseJournalOwnerBoundary(): void
    {
        $primary = $this->ee->db;
        $primary->where('pending', 17);
        $clone = clone $primary;
        $clone->where('site_id', 1)->update('owned_table', array('zero' => 0));
        $fresh = $primary->newQuery();
        $fresh->where('site_id', 2)->insert('other_owned_table', array('zero' => 0));
        $entries = $primary->sharedTrace->journal;
        self::assertSame(array('where', array('pending', 17)), $entries[0]['call']);
        self::assertSame(spl_object_id($clone), $entries[2]['owner']);
        self::assertSame(array(array('where', array('pending', 17)), array('where', array('site_id', 1)), array('update', array('owned_table', array('zero' => 0)))), $entries[2]['calls']);
        self::assertSame(spl_object_id($fresh), $entries[4]['owner']);
        self::assertSame(array(array('where', array('site_id', 2)), array('insert', array('other_owned_table', array('zero' => 0)))), $entries[4]['calls']);
        self::assertSame(array(array('where', array('pending', 17))), $primary->calls);
    }

    /**
     * Verify current EE's isset default separately from bridge behavior.
     *
     * @return void
     */
    public function testSessionFixtureDefaults(): void
    {
        $session = new LegacySession();
        $session->userdata = array('zero' => 0, 'false' => false, 'null' => null);
        self::assertSame(0, $session->userdata('zero', 'missing'));
        self::assertFalse($session->userdata('false', 'missing'));
        self::assertSame('missing', $session->userdata('null', 'missing'));
        self::assertSame('missing', $session->userdata('absent', 'missing'));
    }

    /**
     * Preserve existing directives and select the configured session token.
     *
     * @dataProvider urlProvider
     * @param string $version
     * @param string $base
     * @param array $config
     * @param array $userdata
     * @param string $session
     * @param string $directive
     * @return void
     */
    public function testHistoricalSessionUrls($version, $base, array $config, array $userdata, $session, $directive): void
    {
        $data = $this->native(array('mode' => 'url', 'version' => $version, 'base' => $base, 'config' => $config, 'userdata' => $userdata));
        foreach (array('base' => 'history', 'module' => 'edit') as $name => $method) {
            parse_str(parse_url(str_replace('&amp;', '&', $data[$name]), PHP_URL_QUERY), $query);
            $expected = array('S' => $session, 'C' => 'addons_modules', 'M' => 'show_module_cp', 'module' => 'low_options', 'method' => $method, 'D' => $directive);
            if ($name === 'module') {
                $expected['page'] = '0';
            }
            ksort($expected);
            ksort($query);
            self::assertSame($expected, $query);
        }
    }

    /**
     * Cover session/cookie/default controls and documented workaround boundaries.
     *
     * @return array
     */
    public static function urlProvider(): array
    {
        $base = '/admin.php?S=0';
        return array(
            'session' => array('2.5.5', $base, array('cp_session_type' => 's'), array('session_id' => 'session'), 'session', 'cp'),
            'fingerprint' => array('2.5.5', $base, array('cp_session_type' => 'cs'), array('fingerprint' => 'fingerprint'), 'fingerprint', 'cp'),
            'cookie' => array('2.5.5', $base, array('cp_session_type' => 'c'), array('session_id' => 'unused'), '0', 'cp'),
            'missing' => array('2.5.5', $base, array('cp_session_type' => 's'), array(), '0', 'cp'),
            'null' => array('2.5.5', $base, array('cp_session_type' => 's'), array('session_id' => null), '0', 'cp'),
            'zero' => array('2.5.5', $base, array('cp_session_type' => 's'), array('session_id' => 0), '0', 'cp'),
            'existing' => array('2.5.5', '/admin.php?S=retained&amp;D=custom', array('cp_session_type' => 's'), array('session_id' => 'unused'), 'retained', 'custom'),
            'workaround start' => array('2.6.0', $base, array('admin_session_type' => 's', 'cp_session_type' => 'c'), array('session_id' => 'admin'), 'admin', 'cp'),
            'workaround end' => array('2.7.3', $base, array('admin_session_type' => 's', 'cp_session_type' => 'c'), array('session_id' => 'admin'), 'admin', 'cp'),
        );
    }

    /**
     * Inspect only owned dynamic view files and exact framework handoff.
     *
     * @dataProvider viewProvider
     * @param bool $writable
     * @param bool $return
     * @param string|false $string
     * @param string $name
     * @param string|false $dynamic
     * @return void
     */
    public function testLegacyViewBoundary($writable, $return, $string, $name, $dynamic): void
    {
        $data = $this->native(array('mode' => 'view', 'version' => '2.5.5', 'writable' => $writable, 'return' => $return, 'string' => $string));
        self::assertSame(array('result' => $return ? 'VIEW' : null, 'calls' => array(array('view', array($name, array('zero' => 0, 'ee_ver' => '2'), $return))), 'dynamic' => $dynamic), $data);
    }

    /**
     * Cover absent directory, output-return flag and false/zero source strings.
     *
     * @return array
     */
    public static function viewProvider(): array
    {
        return array(
            array(true, true, '<p>dynamic</p>', 'dynamic', '<p>dynamic</p>'),
            array(false, true, '<p>dynamic</p>', 'fixture', false),
            array(true, false, '<p>dynamic</p>', 'dynamic', '<p>dynamic</p>'),
            array(true, true, false, 'fixture', false),
            array(true, true, '0', 'fixture', false),
        );
    }

    /**
     * Check offset priority and the optional current-page remapping boundary.
     *
     * @dataProvider paginationProvider
     * @param array $get
     * @param int|float $page
     * @param bool $current
     * @return void
     */
    public function testLegacyPaginationState(array $get, $page, $current): void
    {
        $data = $this->native(array('mode' => 'pagination', 'version' => '2.5.5', 'get' => $get, 'current' => $current));
        self::assertSame($page, $data['page']);
        self::assertSame(20, $data['start']);
        self::assertSame('PAGINATION', $data['pagination']);
        $options = array('total_rows' => 42, 'per_page' => 10, 'base_url' => '/items');
        if ($current) {
            $options['current_page'] = 3;
            $options['cur_page'] = 3;
        }
        self::assertSame(array(array('initialize', array($options)), array('create_links', array())), $data['calls']);
        self::assertSame(array(array('library', array('pagination'))), $data['loader']);
    }

    /**
     * Cover defaults, page/offset precedence and fractional quotient behavior.
     *
     * @return array
     */
    public static function paginationProvider(): array
    {
        return array(array(array(), 1, false), array(array('page' => '3'), 3, true), array(array('page' => '4', 'per_page' => '20'), 3, true), array(array('page' => '4', 'per_page' => '0'), 4, false), array(array('per_page' => '1'), 1.1, true), array(array('page' => '0'), 1, false));
    }
}
