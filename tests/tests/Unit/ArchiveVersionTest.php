<?php

namespace LowOptionsTests;

use Low\Options\FluxCapacitor\Conduit\Version;
use Low\Options\FluxCapacitor\Conduit\StaticCache;

class ArchiveVersionTest extends TestCase
{
    /**
     * Configure a license schema matching the shipped table definition.
     *
     * @param bool $exists
     * @return Version
     */
    private function version($exists = true)
    {
        $this->ee->db->tableExists = $exists;
        $this->ee->db->queryResult->num_rows = 1;
        $this->ee->db->queryResult->record = array('Column_name' => 'license_key', 'Non_unique' => '0');
        return new Version();
    }

    /**
     * Verify license table creation and missing ignore column.
     *
     * @return void
     */
    public function testLicenseTableCreationAndMissingIgnoreColumn(): void
    {
        $this->ee->db->fieldExists = false;
        $version = $this->version(false);
        self::assertFalse($version->verifyLicenseTable());
        self::assertContains(array('create_table', array('eeharbor_licenses')), $this->ee->dbforge->calls);
        self::assertContains(array('add_key', array('license_key', true)), $this->ee->dbforge->calls);
        self::assertContains(array('add_column', array('eeharbor_licenses', array('ignore_site' => array('type' => 'int(1)', 'unsigned' => true, 'null' => false, 'default' => '0')))), $this->ee->dbforge->calls);
        self::assertArrayNotHasKey('table_names', $this->ee->db->data_cache);
    }

    /**
     * Detect a missing or altered primary license key.
     *
     * @dataProvider primaryKeyProvider
     * @param int $count
     * @param array $record
     * @return void
     */
    public function testCorruptLicenseTable($count, array $record): void
    {
        $this->ee->db->tableExists = true;
        $this->ee->db->queryResult->num_rows = $count;
        $this->ee->db->queryResult->record = $record;
        $version = new Version();
        self::assertTrue($version->verifyLicenseTable());
    }

    /**
     * Provide primary key vectors.
     *
     * @return array
     */
    public static function primaryKeyProvider(): array
    {
        return array(array(0, array()), array(1, array('Column_name' => 'site_id', 'Non_unique' => '0')), array(1, array('Column_name' => 'license_key', 'Non_unique' => '1')));
    }

    /**
     * Cache existence checks and choose insertion versus scoped update.
     *
     * @dataProvider licenseWriteProvider
     * @param int $count
     * @return void
     */
    public function testLicenseKeyPersistence($count): void
    {
        $version = $this->version();
        $this->ee->db->count = $count;
        self::assertSame($count > 0, $version->keyExistsForAddon());
        $before = count($this->ee->db->calls);
        self::assertSame($count > 0, $version->keyExistsForAddon());
        self::assertCount($before, $this->ee->db->calls);
        $data = array('license_key' => 'test-key');
        $version->saveLicenseKey($data);
        self::assertContains(array($count ? 'update' : 'insert', array('eeharbor_licenses', array('license_key' => 'test-key', 'site_id' => 1, 'addon' => 'Low\\Options'))), $this->ee->db->calls);
        $version->saveLicenseKey(array('site_id' => 3, 'addon' => 'Other', 'license_key' => 'other-key'), 'Other');
        self::assertContains(array($count ? 'update' : 'insert', array('eeharbor_licenses', array('site_id' => 3, 'addon' => 'Other', 'license_key' => 'other-key'))), $this->ee->db->calls);
        $version->deleteLicenseKey();
        self::assertContains(array('delete', array('eeharbor_licenses')), $this->ee->db->calls);
    }

    /**
     * Provide license write vectors.
     *
     * @return array
     */
    public static function licenseWriteProvider(): array
    {
        return array(array(0), array(1));
    }

    /**
     * Verify license key and ignore site defaults and sanitization.
     *
     * @return void
     */
    public function testLicenseKeyAndIgnoreSiteDefaultsAndSanitization(): void
    {
        $version = $this->version();
        self::assertSame('', $version->getLicenseKey());
        self::assertFalse($version->getIgnoreSite());
        $this->ee->db->record = array('license_key' => ' <key_123-ABC> ', 'ignore_site' => '1');
        self::assertSame('key123-ABC', $version->getLicenseKey());
        self::assertSame('1', $version->getIgnoreSite());
    }

    /**
     * Verify version check adds one encrypted pixel and is idempotent.
     *
     * @return void
     */
    public function testVersionCheckAddsOneEncryptedPixelAndIsIdempotent(): void
    {
        $data = $this->cryptoProbe('version');
        self::assertCount(1, $data['first']);
        self::assertStringContainsString('<div id="low_options__up"', $data['first'][0]);
        self::assertStringContainsString('&#104;&#116;&#116;&#112;&#115;', $data['first'][0]);
        self::assertTrue($data['second']);
        self::assertSame($data['first'], $data['footer']);
    }

    /**
     * Verify version check can use local development pixel.
     *
     * @return void
     */
    public function testVersionCheckCanUseLocalDevelopmentPixel(): void
    {
        $data = $this->cryptoProbe('version', array('development' => true));
        self::assertStringContainsString('&#108;&#111;&#99;&#97;&#108;&#104;&#111;&#115;&#116;', $data['first'][0]);
    }

    /**
     * Preserve module presence and typed metadata in the encrypted update payload.
     *
     * @dataProvider proPayloadProvider
     * @param array $record
     * @param int|string $expected
     * @return void
     */
    public function testVersionCheckEncryptedPayload(array $record, $expected): void
    {
        $data = $this->cryptoProbe('payload', array('record' => $record));
        self::assertSame($record, $data['pro']);
        self::assertContains(array('where', array('module_name', 'Pro')), $data['calls']);
        self::assertSame(1, $data['footer_count']);
        self::assertSame(1, $data['matched']);
        self::assertTrue($data['decrypted']);
        $expectedPayload = array(
            'api' => '1', 'l' => '', 'a' => 'low_options', 'v' => '2.2.0',
            'e' => '7.5.0', 'p' => PHP_VERSION, 'd' => 'https://example.test/',
            'c' => false, 's' => 1, 'pi' => $expected, 'pl' => 'test-site-key', 'lc' => false,
        );
        $payloadData = $data['payload'];
        ksort($expectedPayload);
        ksort($payloadData);
        self::assertSame($expectedPayload, $payloadData);
    }

    /**
     * Provide absent and installed Pro database result shapes.
     *
     * @return array
     */
    public static function proPayloadProvider(): array
    {
        return array(
            'absent' => array(array(), 0),
            'installed' => array(array('module_version' => '2.1.0'), '2.1.0'),
        );
    }

    /**
     * Verify spider safe converts every byte including punctuation.
     *
     * @return void
     */
    public function testSpiderSafeConvertsEveryByteIncludingPunctuation(): void
    {
        $version = $this->version();
        self::assertSame('&#65;&#38;&#32;&#48;', $this->invoke($version, 'spiderSafe', array('A& 0')));
        self::assertSame('', $this->invoke($version, 'spiderSafe', array('')));
        self::assertSame(array('loaded' => true), $this->cryptoProbe('public-key'));
    }
}
