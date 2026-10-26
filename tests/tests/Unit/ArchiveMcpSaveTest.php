<?php

namespace LowOptionsTests;

use Low\Options\FluxCapacitor\Base\Mcp;

class ArchiveMcpSaveTest extends TestCase
{
    private $alert;

    /**
     * Prepare a real MCP/Version bridge with table-specific result fixtures.
     *
     * @return Mcp
     */
    private function controller()
    {
        $mcp = new Mcp();
        $this->ee->db->tableExists = true;
        $this->ee->db->queryResult->num_rows = 1;
        $this->ee->db->queryResult->record = array('Column_name' => 'license_key', 'Non_unique' => '0');
        $this->ee->services['Security/XSS'] = new IdentitySecurity();
        $this->alert = new Alert();
        $this->ee->services['CP/Alert'] = $this->alert;
        return $mcp;
    }

    /**
     * Run a terminating EE controller request and retain its redirect target.
     *
     * @param Mcp $mcp
     * @return void
     */
    private function save(Mcp $mcp)
    {
        try {
            $mcp->save_license();
            self::fail('Saving a license did not terminate with an EE redirect.');
        } catch (Redirect $redirect) {
            self::assertSame('addons/settings/low_options/license', $redirect->getMessage());
        }
    }

    /**
     * Save sanitized keys or ignored-site keys through the correct scoped write.
     *
     * @dataProvider saveProvider
     * @param string $key
     * @param int $ignore
     * @param int $exists
     * @param string $expected
     * @return void
     */
    public function testLicenseSaving($key, $ignore, $exists, $expected): void
    {
        $mcp = $this->controller();
        $this->ee->input->post = array('license_key' => $key, 'ignore_site' => $ignore);
        $this->ee->db->count = $exists;
        $this->save($mcp);
        self::assertContains(array($exists ? 'update' : 'insert', array('eeharbor_licenses', array(
            'site_id' => 1, 'addon' => 'Low\\Options', 'license_key' => $expected, 'ignore_site' => $ignore,
        ))), $this->ee->db->calls);
        self::assertContains(array('withTitle', array('License Key Updated')), $this->alert->calls);
        if ($exists) {
            self::assertContains(array('where', array('site_id', 1)), $this->ee->db->calls);
            self::assertContains(array('where', array('addon', 'Low\\Options')), $this->ee->db->calls);
        }
    }

    /**
     * Provide sanitized, ignored-site and maximum-length persistence vectors.
     *
     * @return array
     */
    public static function saveProvider(): array
    {
        return array(
            array(' ABC-123 <>_ ', 0, 0, 'ABC-123'),
            array('test-key', 0, 1, 'test-key'),
            array('discarded-key', 1, 0, 'low-options-ignore-site-1'),
            array(str_repeat('a', 50), 0, 0, str_repeat('a', 50)),
        );
    }

    /**
     * Reject an oversized license before any license write.
     *
     * @return void
     */
    public function testOverlongLicenseStopsBeforeWrite(): void
    {
        $mcp = $this->controller();
        $this->ee->input->post = array('license_key' => str_repeat('a', 51), 'ignore_site' => 0);
        $this->save($mcp);
        self::assertContains(array('withTitle', array('License Key Too Long')), $this->alert->calls);
        self::assertSame(array(), $this->licenseMutations());
    }

    /**
     * Reject license reuse across sites and preserve the current site's record.
     *
     * @dataProvider otherSiteProvider
     * @param array $site
     * @param string $expected
     * @return void
     */
    public function testLicenseOnAnotherSiteStopsBeforeWrite(array $site, $expected): void
    {
        $mcp = $this->controller();
        $this->ee->input->post = array('license_key' => 'used-key', 'ignore_site' => 0);
        $this->ee->db->record = array('site_id' => 2, 'addon' => 'Low\\Options', 'license_key' => 'used-key');
        $result = new Query();
        $result->record = $site;
        $this->ee->db->results['sites'] = $result;
        $this->save($mcp);
        $titles = array_filter($this->alert->calls, function ($call) { return $call[0] === 'withTitle'; });
        self::assertStringContainsString($expected, array_values($titles)[0][1][0]);
        self::assertSame(array(), $this->licenseMutations());
        self::assertContains(array('get_where', array('sites', array('site_id' => 2), 1)), $this->ee->db->calls);
    }

    /**
     * Provide existing and removed MSM-site descriptions.
     *
     * @return array
     */
    public static function otherSiteProvider(): array
    {
        return array(array(array('site_label' => 'Other Site'), 'Other Site'), array(array(), 'Could not locate MSM Site name'));
    }

    /**
     * Reject a key belonging to a different installed add-on.
     *
     * @return void
     */
    public function testLicenseForAnotherAddonStopsBeforeWrite(): void
    {
        $mcp = $this->controller();
        $this->ee->input->post = array('license_key' => 'used-key', 'ignore_site' => 0);
        $this->ee->db->record = array('site_id' => 1, 'addon' => 'EEHarbor\\OtherAddon', 'license_key' => 'used-key');
        $this->save($mcp);
        $titles = array_filter($this->alert->calls, function ($call) { return $call[0] === 'withTitle'; });
        self::assertStringContainsString('another installed add-on', array_values($titles)[0][1][0]);
        self::assertSame(array(), $this->licenseMutations());
    }

    /**
     * Remove an ignored-site record when explicitly returning to unlicensed mode.
     *
     * @return void
     */
    public function testUnignoringAnEmptyKeyDeletesIgnoredSiteRecord(): void
    {
        $mcp = $this->controller();
        $this->ee->input->post = array('license_key' => '', 'ignore_site' => 0);
        $this->ee->db->count = 1;
        $this->ee->db->record = array('site_id' => 1, 'addon' => 'Low\\Options', 'license_key' => 'low-options-ignore-site-1');
        $this->save($mcp);
        self::assertSame(array(array('delete', array('eeharbor_licenses'))), $this->licenseMutations());
        self::assertSame(array(
            array('where', array('site_id', 1)),
            array('where', array('addon', 'Low\\Options')),
            array('delete', array('eeharbor_licenses')),
        ), array_slice($this->ee->db->calls, -3));
    }

    /**
     * Keep counting a cloned model separate from applying the requested window.
     *
     * @return void
     */
    public function testPaginationCountsWithoutLimitingOriginalQuery(): void
    {
        $mcp = $this->controller();
        $query = new Query();
        $query->values = array('one', 'two', 'three');
        $this->ee->input->get['page'] = '2';
        $this->ee->services['CP/Pagination'] = $this->ee->pagination;
        try {
            $result = $mcp->paginateModel($query, '/history', 2, 3);
        } finally {
            self::assertContains(array('CP/Pagination', array(3)), $this->ee->serviceCalls);
            self::assertCount(2, $query->collectionTrace->calls);
            $cloneId = $query->collectionTrace->calls[0][1];
            self::assertNotSame(spl_object_id($query), $cloneId);
            self::assertSame(array(array('all', $cloneId), array('count', $cloneId, 3)), $query->collectionTrace->calls);
        }
        self::assertSame($query, $result);
        self::assertSame(array(array('limit', array(2)), array('offset', array(2))), $query->calls);
        self::assertSame('PAGINATION', $mcp->data['pagination']);
        self::assertSame(array('perPage', array(2)), $this->ee->pagination->calls[0]);
    }

    /**
     * Retrieve only writes that could change the stored license.
     *
     * @return array
     */
    private function licenseMutations()
    {
        return array_values(array_filter($this->ee->db->calls, function ($call) {
            return in_array($call[0], array('insert', 'update', 'delete'), true) && $call[1][0] === 'eeharbor_licenses';
        }));
    }
}
