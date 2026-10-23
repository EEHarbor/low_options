<?php

namespace LowOptionsTests;

use Low\Options\FluxCapacitor\Conduit\StaticCache;
use Low\Options\FluxCapacitor\Conduit\PersistentCache;
use Low\Options\FluxCapacitor\Conduit\GlobalFieldSettings;

class ArchiveConduitTest extends TestCase
{
    /**
     * Restore diagnostics and release request-local state.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        if (is_dir(PATH_CACHE . 'low_options')) {
            foreach (glob(PATH_CACHE . 'low_options/*') as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir(PATH_CACHE . 'low_options');
        }
        if (is_dir(PATH_CACHE)) {
            rmdir(PATH_CACHE);
        }
        parent::tearDown();
    }

    /**
     * Verify static cache stores retrieves aliases deletes and clears.
     *
     * @return void
     */
    public function testStaticCacheStoresRetrievesAliasesDeletesAndClears(): void
    {
        self::assertFalse(StaticCache::get('missing'));
        self::assertFalse(StaticCache::has('missing'));
        StaticCache::put(array('one', 'two'), array('value' => 7));
        self::assertSame(array('value' => 7), StaticCache::get('one:two'));
        self::assertTrue(StaticCache::has(array('one', 'two')));
        $key = sha1('one:two');
        self::assertSame(array('value' => 7), StaticCache::get($key, true));
        self::assertSame(array($key => array('value' => 7)), StaticCache::all());
        StaticCache::set('alias', 'stored');
        self::assertSame('stored', StaticCache::get('alias'));
        StaticCache::delete(array('one', 'two'));
        self::assertFalse(StaticCache::has($key, true));
        self::assertTrue(StaticCache::clear());
        self::assertSame(array(), StaticCache::all());
    }

    /**
     * Retain all meaningful stored values.
     *
     * @dataProvider cacheValuesProvider
     * @param mixed $value
     * @return void
     */
    public function testStaticCachePreservesFalsyValues($value): void
    {
        StaticCache::put('value', $value);
        self::assertSame($value, StaticCache::get('value'));
    }

    /**
     * Provide cache values vectors.
     *
     * @return array
     */
    public static function cacheValuesProvider(): array
    {
        return array(array(0), array('0'), array(''), array(array()), array(false), array(null));
    }

    /**
     * Verify persistent cache creates paths and round trips disk values.
     *
     * @return void
     */
    public function testPersistentCacheCreatesPathsAndRoundTripsDiskValues(): void
    {
        self::assertFalse(PersistentCache::get('missing'));
        self::assertCount(1, $this->ee->logger->messages);
        $value = array('id' => 7, 'text' => 'value', 'nullable' => null);
        self::assertTrue(PersistentCache::set(array('one', 'two'), $value));
        self::assertTrue(PersistentCache::has('one:two'));
        self::assertSame($value, PersistentCache::get('one:two'));
        StaticCache::clear();
        self::assertSame($value, PersistentCache::get('one:two'));
        self::assertSame(serialize($value), file_get_contents(PATH_CACHE . 'low_options/' . sha1('one:two')));
        self::assertTrue(PersistentCache::delete('one:two'));
        self::assertFalse(PersistentCache::has('one:two'));
        self::assertFalse(StaticCache::has(sha1('one:two'), true));
        self::assertTrue(PersistentCache::delete('missing'));
    }

    /**
     * Verify persistent second disk read returns deserialized value.
     *
     * @return void
     */
    public function testPersistentSecondDiskReadReturnsDeserializedValue(): void
    {
        $value = array('payload' => 'unchanged');
        PersistentCache::set('read-twice', $value);
        StaticCache::clear();
        self::assertSame($value, PersistentCache::get('read-twice'));
        self::assertSame($value, PersistentCache::get('read-twice'));
    }

    /**
     * Verify persistent clear leaves other static entries alone.
     *
     * @return void
     */
    public function testPersistentClearLeavesOtherStaticEntriesAlone(): void
    {
        StaticCache::put('other-addon', 'kept');
        PersistentCache::set('first', array(1));
        PersistentCache::set('second', array(2));
        self::assertTrue(PersistentCache::clear());
        self::assertFalse(PersistentCache::has('first'));
        self::assertFalse(PersistentCache::has('second'));
        self::assertFalse(StaticCache::has(sha1('first'), true));
        self::assertSame('kept', StaticCache::get('other-addon'));
    }

    /**
     * Verify global field settings constructor uses addon name or explicit name.
     *
     * @return void
     */
    public function testGlobalFieldSettingsConstructorUsesAddonNameOrExplicitName(): void
    {
        self::assertSame('low_options', (new GlobalFieldSettings())->fieldtype_name);
        self::assertSame('custom', (new GlobalFieldSettings('custom'))->fieldtype_name);
    }

    /**
     * Verify global field settings reads legacy encoded array and updates only requested key.
     *
     * @return void
     */
    public function testGlobalFieldSettingsReadsLegacyEncodedArrayAndUpdatesOnlyRequestedKey(): void
    {
        $settings = new GlobalFieldSettings('select');
        $this->ee->db->num_rows = 1;
        $this->ee->db->record = array('settings' => base64_encode(serialize(array('old' => 'kept', 'zero' => 0))));
        self::assertSame(array('old' => 'kept', 'zero' => 0), $settings->getSettings());
        self::assertSame(0, $settings->getSetting('zero'));
        self::assertTrue($settings->updateSetting('new', false));
        self::assertContains(array('where', array('name', 'select')), $this->ee->db->calls);
        self::assertContains(array('update', array('fieldtypes', array('settings' => base64_encode(serialize(array('old' => 'kept', 'zero' => 0, 'new' => false)))))), $this->ee->db->calls);
    }

    /**
     * Verify missing global field settings return null and are not inserted.
     *
     * @return void
     */
    public function testMissingGlobalFieldSettingsReturnNullAndAreNotInserted(): void
    {
        $settings = new GlobalFieldSettings();
        self::assertNull($settings->getSettings());
        self::assertNull($settings->getSetting('missing'));
        self::assertFalse($settings->updateSetting('new', 'value'));
        self::assertSame(array(), array_filter($this->ee->db->calls, function ($call) { return $call[0] === 'update'; }));
    }

    /**
     * Verify serialized false settings normalize to empty array.
     *
     * @return void
     */
    public function testSerializedFalseSettingsNormalizeToEmptyArray(): void
    {
        $settings = new GlobalFieldSettings();
        $this->ee->db->num_rows = 1;
        $this->ee->db->record = array('settings' => base64_encode(serialize(false)));
        self::assertSame(array(), $settings->getSettings());
    }

    /**
     * Verify missing named setting does not emit suppressed undefined key diagnostic.
     *
     * @return void
     */
    public function testMissingNamedSettingDoesNotEmitSuppressedUndefinedKeyDiagnostic(): void
    {
        $settings = new GlobalFieldSettings();
        $this->ee->db->num_rows = 1;
        $this->ee->db->record = array('settings' => base64_encode(serialize(array('present' => 1))));
        self::assertNull($settings->getSetting('absent'));
    }

    /**
     * Verify malformed settings do not emit suppressed unserialize diagnostic.
     *
     * @return void
     */
    public function testMalformedSettingsDoNotEmitSuppressedUnserializeDiagnostic(): void
    {
        $settings = new GlobalFieldSettings();
        $this->ee->db->num_rows = 1;
        $this->ee->db->record = array('settings' => base64_encode('not serialized'));
        self::assertSame(array(), $settings->getSettings());
    }
}
