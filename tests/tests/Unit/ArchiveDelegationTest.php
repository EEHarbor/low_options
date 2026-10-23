<?php

namespace LowOptionsTests;

class ArchiveDelegationTest extends TestCase
{
    /**
     * Provide bridge vectors.
     *
     * @return array
     */
    public static function bridgeProvider(): array
    {
        return ArchiveBridgeTest::bridgeProvider();
    }

    /**
     * Construct a shipped bridge class.
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
     * Assert wrapper side effects instead of exercising framework internals.
     *
     * @dataProvider bridgeProvider
     * @param string $version
     * @return void
     */
    public function testNativeServiceDelegation($version): void
    {
        $bridge = $this->bridge($version);
        $bridge->instantiate('channel_entries');
        $api = $version === 'EE2' ? $this->ee->api : $this->ee->legacy_api;
        self::assertSame(array(array('instantiate', array('channel_entries'))), $api->calls);
        $bridge->overwriteEEClass('custom', 'replacement');
        self::assertSame('replacement', $this->ee->custom);
        $bridge->removeEEClass('custom');
        self::assertSame($version === 'EE2' ? '' : null, $this->ee->custom);
        $security = new ServiceRecorder();
        $security->result = array('clean');
        $this->ee->security = $security;
        $this->ee->services['Security/XSS'] = $security;
        self::assertSame(array('clean'), $bridge->xss_clean(array('<script>')));
        self::assertSame(array(array($version === 'EE2' ? 'xss_clean' : 'clean', array(array('<script>')))), $security->calls);
        $this->ee->services['Format'] = new Formatter();
        self::assertSame('my-title', $bridge->url_title(' My Title '));
        self::assertSame('https://example.test/a/b', $bridge->reduce_double_slashes('https://example.test//a///b'));
        if ($version !== 'EE2') {
            self::assertSame('http://example.test', $bridge->prep_url('example.test'));
        }
    }

    /**
     * Forward variable parsing and installation checks to the EE-version service.
     *
     * @dataProvider bridgeProvider
     * @param string $version
     * @return void
     */
    public function testParserAndModuleDelegation($version): void
    {
        $bridge = $this->bridge($version);
        $parser = new ServiceRecorder();
        $parser->result = array('sentinel' => 'parsed');
        $this->ee->services['Variables/Parser'] = $parser;
        $this->ee->functions->result = $parser->result;
        self::assertSame($parser->result, $bridge->assign_variables('{title}'));
        self::assertSame($parser->result, $bridge->assign_parameters('limit="10"', array('limit' => 5)));
        if ($version === 'EE2' || $version === 'EE3') {
            self::assertSame(array(
                array('assign_variables', array('{title}')),
                array('assign_parameters', array('limit="10"', array('limit' => 5))),
            ), $this->ee->functions->calls);
        } else {
            self::assertSame(array(
                array('extractVariables', array('{title}')),
                array('parseTagParameters', array('limit="10"', array('limit' => 5))),
            ), $parser->calls);
        }
        $this->ee->db->count = 1;
        $this->ee->addons_model->result = true;
        self::assertTrue($bridge->module_installed('Example'));
        $this->ee->db->count = 0;
        $this->ee->addons_model->result = false;
        self::assertFalse($bridge->module_installed('Missing'));
    }

    /**
     * Respect complete and missing route portions without losing state.
     *
     * @dataProvider bridgeProvider
     * @param string $version
     * @return void
     */
    public function testCurrentUrlInformation($version): void
    {
        $bridge = $this->bridge($version);
        $oldGet = $_GET;
        $hadQuery = array_key_exists('QUERY_STRING', $_SERVER);
        $oldQuery = $hadQuery ? $_SERVER['QUERY_STRING'] : null;
        try {
            $_GET = array('module' => 'low_options', 'method' => 'history');
            $_SERVER['QUERY_STRING'] = 'cp/addons/settings/low_options/history/';
            $this->ee->uri->path = 'cp/addons/settings/low_options/history';
            $info = $bridge->getCurrentUrlInfo();
            self::assertTrue($info['cp']);
            self::assertSame('low_options', $info['module']);
            self::assertSame('history', $bridge->getCurrentUrlInfo('method'));
            self::assertSame($info, $bridge->getCurrentUrlInfo('unknown'));
            unset($_GET['method']);
            $this->ee->uri->path = 'cp/addons/settings/low_options';
            self::assertSame('index', $bridge->getCurrentUrlInfo('method'));
        } finally {
            $_GET = $oldGet;
            if ($hadQuery) {
                $_SERVER['QUERY_STRING'] = $oldQuery;
            } else {
                unset($_SERVER['QUERY_STRING']);
            }
        }
    }

    /**
     * Choose modern alert severities and apply only defined extra methods.
     *
     * @dataProvider alertProvider
     * @param string $version
     * @param string $type
     * @param string $severity
     * @return void
     */
    public function testFlashMessages($version, $type, $severity): void
    {
        $bridge = $this->bridge($version);
        $alert = new Alert();
        $this->ee->services['CP/Alert'] = $alert;
        $bridge->flashData($type, 'Title', 'Body', array('cannotClose', 'missingMethod'));
        if ($version === 'EE2') {
            self::assertSame(array($type => 'Title Body'), $this->ee->session->flash);
            return;
        }
        self::assertSame(array(
            array($version === 'EE3' ? 'make' : 'makeInline', array('Title')), array($severity, array()), array('withTitle', array('Title')),
            array('addToBody', array('Body')), array('canClose', array()), array('cannotClose', array()), array('defer', array()),
        ), $alert->calls);
    }

    /**
     * Provide alert vectors.
     *
     * @return array
     */
    public static function alertProvider(): array
    {
        $rows = array();
        foreach (array('EE2', 'EE3', 'EE4', 'EE5') as $version) {
            foreach (array('message_error' => 'asIssue', 'message_failure' => 'asIssue', 'message_notice' => 'asWarning', 'message_success' => 'asSuccess') as $type => $severity) {
                $rows[] = array($version, $type, $severity);
            }
        }
        return $rows;
    }

    /**
     * Produce internal/external navigation, buttons, active maps and breadcrumbs.
     *
     * @dataProvider modernBridgeProvider
     * @param string $version
     * @return void
     */
    public function testNavigationAndActiveMaps($version): void
    {
        $bridge = $this->bridge($version);
        $sidebar = new Sidebar();
        $this->ee->services['CP/Sidebar'] = $sidebar;
        $bridge->getNav(array('/' => 'Home', 'history' => 'History', 'https://example.test' => 'External'), array('history' => array('new' => 'Add')));
        self::assertCount(3, $sidebar->items);
        self::assertTrue($sidebar->items[0]->active);
        self::assertSame('https://example.test', $sidebar->items[2]->url);
        self::assertSame('Add', $sidebar->items[1]->buttons[0][0]);
        self::assertSame('Home', $this->ee->view->cp_page_title);
        $this->ee->uri->path .= '/detail';
        $bridge->getNav(array('history' => 'History'), array(), array('detail' => 'history'));
        self::assertTrue($sidebar->items[3]->active);
        self::assertCount(2, $this->ee->cp->breadcrumbs);
        $this->ee->uri->path = 'cp/unmatched';
        $bridge->getNav(array('history' => 'History'));
        self::assertFalse($sidebar->items[4]->active);
    }

    /**
     * Provide modern bridge vectors.
     *
     * @return array
     */
    public static function modernBridgeProvider(): array
    {
        return ArchiveBridgeTest::modernBridgeProvider();
    }
}
