<?php

namespace LowOptionsTests;

use Low\Options\FluxCapacitor\Conduit\GlobalFieldSettings;
use Low\Options\FluxCapacitor\Conduit\PersistentCache;
use Low\Options\FluxCapacitor\Conduit\SerializedValue;
use Low\Options\FluxCapacitor\Conduit\StaticCache;

class CompatibilityFixTest extends TestCase
{
    /**
     * Preserve computed defaults provided by stored ArrayAccess roots.
     *
     * @return void
     */
    public function testArrayAccessSettingsGetter(): void
    {
        $this->ee->db->num_rows = 1;
        $this->ee->db->record = array('settings' => base64_encode(serialize(new ComputedSettings(array('saved' => 7)))));
        $settings = new GlobalFieldSettings();
        self::assertSame(7, $settings->getSetting('saved'));
        self::assertSame('computed-default', $settings->getSetting('missing'));
    }

    /**
     * Preserve nested objects written through the existing public settings API.
     *
     * @return void
     */
    public function testSettingsWriterRoundTrip(): void
    {
        $this->ee->db->num_rows = 1;
        $this->ee->db->record = array('settings' => base64_encode(serialize(array('keep' => 7))));
        $settings = new GlobalFieldSettings();
        $value = array('layout' => (object) array('name' => 'compact'));
        self::assertTrue($settings->updateSetting('preferences', $value));
        $writes = array_values(array_filter($this->ee->db->calls, function ($call) { return $call[0] === 'update'; }));
        $blob = $writes[0][1][1]['settings'];
        $this->ee->db->record = array('settings' => $blob);
        self::assertSame(base64_decode($blob), serialize($settings->getSettings()));
        self::assertInstanceOf(\stdClass::class, $settings->getSetting('preferences')['layout']);
        self::assertTrue($settings->updateSetting('other', false));
        $writes = array_values(array_filter($this->ee->db->calls, function ($call) { return $call[0] === 'update'; }));
        self::assertCount(2, $writes);
    }

    /**
     * Preserve valid native types, noncanonical spellings and reference aliases.
     *
     * @dataProvider serializedValues
     * @param string $serialized
     * @param mixed $expected
     * @return void
     */
    public function testNativeStorageFormats($serialized, $expected): void
    {
        self::assertSame(serialize($expected), serialize(SerializedValue::decode($serialized)));
    }

    /**
     * Include valid object roots, false-like values and native array spellings.
     *
     * @return array
     */
    public static function serializedValues(): array
    {
        $shared = 1;
        $references = array('x' => &$shared, 'y' => &$shared);
        $object = (object) array('name' => 'kept');
        $root = new \ArrayObject(array('object' => $object));
        return array(
            array('a:1:{s:1:"x";i:+1;}', array('x' => 1)),
            array('a:1:{s:1:"x";i:01;}', array('x' => 1)),
            array('a:1:{s:1:"x";d:+1;}', array('x' => 1.0)),
            array('a:2:{s:1:"x";i:1;s:1:"x";i:2;}', array('x' => 2)),
            array('a:2:{s:1:"x";i:1;s:1:"y";R:02;}', $references),
            array(serialize(array('object' => $object, 'same' => $object)), array('object' => $object, 'same' => $object)),
            array(serialize($root), $root),
            array('b:0;', false), array('N;', null), array('i:0;', 0),
        );
    }

    /**
     * Restore the caller's diagnostic authority after valid and malformed parses.
     *
     * @return void
     */
    public function testDecoderRestoresHandler(): void
    {
        $handler = Diagnostics::handler();
        self::assertFalse(SerializedValue::decode('not serialized'));
        self::assertSame(array(), SerializedValue::decode('a:0:{}'));
        $actual = set_error_handler(function () { return false; });
        restore_error_handler();
        self::assertSame($handler, $actual);
        $decoded = SerializedValue::decode('a:2:{s:1:"x";i:1;s:1:"y";R:02;}');
        $decoded['x'] = 9;
        self::assertSame(9, $decoded['y']);
    }

    /**
     * Keep decoded false-like values after the backing cache file disappears.
     *
     * @dataProvider warmValues
     * @param mixed $value
     * @return void
     */
    public function testDecodedWarmCache($value): void
    {
        try {
            self::assertTrue(PersistentCache::set('warm-value', $value));
            StaticCache::clear();
            self::assertSame(serialize($value), serialize(PersistentCache::get('warm-value')));
            unlink(PATH_CACHE . 'low_options/' . sha1('warm-value'));
            self::assertSame(serialize($value), serialize(PersistentCache::get('warm-value')));
        } finally {
            if (is_dir(PATH_CACHE . 'low_options')) {
                rmdir(PATH_CACHE . 'low_options');
            }
            if (is_dir(PATH_CACHE)) {
                rmdir(PATH_CACHE);
            }
        }
    }

    /**
     * Cover null, false, zero and documented empty-array payloads.
     *
     * @return array
     */
    public static function warmValues(): array
    {
        return array(array(null), array(false), array(0), array(array()));
    }
}

class ComputedSettings extends \ArrayObject
{
    /**
     * Model a valid stored object whose getter computes an absent value.
     *
     * @param mixed $key
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($key)
    {
        return parent::offsetExists($key) ? parent::offsetGet($key) : 'computed-default';
    }
}
