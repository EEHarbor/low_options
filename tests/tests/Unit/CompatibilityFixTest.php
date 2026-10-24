<?php

namespace LowOptionsTests;

use Low\Options\FluxCapacitor\Conduit\GlobalFieldSettings;
use Low\Options\FluxCapacitor\Conduit\PersistentCache;
use Low\Options\FluxCapacitor\Conduit\SerializedValue;
use Low\Options\FluxCapacitor\Conduit\StaticCache;

class CompatibilityFixTest extends TestCase
{
    /**
     * Preserve native hydration callbacks even when a later token is malformed.
     *
     * @dataProvider malformedTails
     * @param string $tail
     * @return void
     */
    public function testMalformedTailCallbackException($tail): void
    {
        $stored = 'a:3:{i:0;S:1:"\41";i:1;' . serialize(new ThrowingSettingsCallback()) . 'i:2;' . $tail . '}';
        try {
            SerializedValue::decode($stored);
            self::fail('Native callback exceptions must propagate after malformed storage.');
        } catch (\RuntimeException $exception) {
            self::assertSame('owned native callback exception', $exception->getMessage());
        }
        $handler = set_error_handler(function () {});
        restore_error_handler();
        self::assertSame(Diagnostics::handler(), $handler);
    }

    /**
     * Include incomplete escaped data, invalid hex, ordinary strings and tokens.
     *
     * @return array
     */
    public static function malformedTails(): array
    {
        return array(array('S:9:"bad";'), array('S:1:"\zz";'), array('s:9:"bad S:";'), array('!S:'), array('C:1:"X":-999:{S:}'));
    }

    /**
     * Keep extraction cleanup inside the actual owned directory boundary.
     *
     * @dataProvider extractionLinks
     * @param string $mode
     * @param string $location
     * @return void
     */
    public function testExtractionLinkBoundaries($mode, $location): void
    {
        require_once LOW_OPTIONS_ROOT . '/tests/Support/NativeProcess.php';
        $result = NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/extraction-link-probe.php', array($mode, $location));
        self::assertSame(0, $result[0], $result[2] . $result[1]);
        self::assertSame('', $result[2]);
        self::assertSame(array('blocked' => $mode !== 'cleanup', 'sentinel' => 'retained', 'link' => $mode !== 'cleanup', 'cwd_preserved' => true, 'target_removed' => true, 'sibling_preserved' => true), json_decode($result[1], true));
    }

    /**
     * Exercise actual go, no-wrapper fallback and direct helper root links.
     *
     * @return array
     */
    public static function extractionLinks(): array
    {
        return array(array('go', 'root'), array('go', 'container'), array('fallback', 'root'), array('fallback', 'container'), array('cleanup', 'root'), array('cleanup', 'dangling'), array('cleanup', 'trailing'), array('cleanup', 'backslash'));
    }

    /**
     * Read legacy escaped data through actual settings and persistent cache APIs.
     *
     * @return void
     */
    public function testLegacyEscapedStorageCallers(): void
    {
        $stored = 'a:1:{s:4:"name";S:1:"\41";}unused';
        $this->ee->db->num_rows = 1;
        $this->ee->db->record = array('settings' => base64_encode($stored));
        self::assertSame(array('name' => 'A'), (new GlobalFieldSettings())->getSettings());
        self::assertSame(base64_encode($stored), $this->ee->db->record['settings']);
        try {
            self::assertTrue(PersistentCache::set('legacy-escaped', array()));
            file_put_contents(PATH_CACHE . 'low_options/' . sha1('legacy-escaped'), $stored);
            StaticCache::clear();
            self::assertSame(array('name' => 'A'), PersistentCache::get('legacy-escaped'));
            self::assertSame($stored, file_get_contents(PATH_CACHE . 'low_options/' . sha1('legacy-escaped')));
        } finally {
            PersistentCache::delete('legacy-escaped');
            rmdir(PATH_CACHE . 'low_options');
            rmdir(PATH_CACHE);
        }
    }

    /**
     * Preserve reference identities and leave custom serialization payloads opaque.
     *
     * @return void
     */
    public function testEscapedReferencesAndOpaquePayloads(): void
    {
        $decoded = SerializedValue::decode('a:2:{s:1:"x";S:1:"\41";s:1:"y";R:02;}');
        $decoded['x'] = 'changed';
        self::assertSame('changed', $decoded['y']);
        $decoded = SerializedValue::decode('a:2:{i:0;O:8:"stdClass":+1:{s:1:"x";S:1:"\41";}i:1;r:2;}');
        self::assertSame($decoded[0], $decoded[1]);
        self::assertSame('A', $decoded[0]->x);
        $name = OpaqueSettingsPayload::class;
        $payload = 'S:1:"\41";}opaque';
        $stored = 'C:' . strlen($name) . ':"' . $name . '":+' . strlen($payload) . ':{' . $payload . '}';
        self::assertSame($payload, SerializedValue::decode($stored)->payload);
    }

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
            array('S:3:"A\42C";', 'ABC'),
            array('S:3:"\00\ffA";', "\0\xffA"),
            array('a:1:{s:1:"x";S:1:"\41";}', array('x' => 'A')),
            array('O:8:"stdClass":+1:{s:1:"x";S:1:"\41";}', (object) array('x' => 'A')),
            array('O:08:"stdClass":+01:{s:1:"x";S:1:"\41";}', (object) array('x' => 'A')),
            array('O:8:"stdClass":-0:{}unused S:', new \stdClass()),
            array(serialize('S:1:"\41";'), 'S:1:"\41";'),
            array('S:1:"\41";trailing', 'A'),
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

class OpaqueSettingsPayload implements \Serializable
{
    public $payload;

    /**
     * Return the custom format's uninterpreted bytes.
     *
     * @return string
     */
    public function serialize(): string
    {
        return $this->payload;
    }

    /**
     * Retain the custom payload passed by the native parser.
     *
     * @param string $data
     * @return void
     */
    public function unserialize($data): void
    {
        $this->payload = $data;
    }

    /**
     * Support the modern format without deprecating the legacy interface.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return array('payload' => $this->payload);
    }

    /**
     * Restore values created through modern serialization.
     *
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->payload = $data['payload'];
    }
}

class ThrowingSettingsCallback
{
    /**
     * Expose the native callback behavior retained after a later parse error.
     *
     * @return void
     * @throws \RuntimeException
     */
    public function __wakeup(): void
    {
        throw new \RuntimeException('owned native callback exception');
    }
}
