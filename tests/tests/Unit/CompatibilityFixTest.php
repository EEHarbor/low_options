<?php

namespace LowOptionsTests;

use Low\Options\FluxCapacitor\Conduit\GlobalFieldSettings;
use Low\Options\FluxCapacitor\Conduit\PersistentCache;
use Low\Options\FluxCapacitor\Conduit\SerializedValue;
use Low\Options\FluxCapacitor\Conduit\StaticCache;

class CompatibilityFixTest extends TestCase
{
    /**
     * Preserve native first-resolution lifetime, ordering and inherited callers.
     *
     * @dataProvider nativeCalleeLifetimes
     * @param string $route
     * @param string $scenario
     * @param string $expected
     * @return void
     */
    public function testNativeCalleeLifetime($route, $scenario, $expected): void
    {
        require_once LOW_OPTIONS_ROOT . '/tests/Support/NativeProcess.php';
        $result = NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/callee-lifetime-probe.php', array(
            dirname(__DIR__, 3) . '/low_options/low_options.phar', 'Low\\Options', $scenario, $route
        ));
        self::assertSame(0, $result[0], $result[2] . $result[1]);
        self::assertSame('', $result[2]);
        self::assertSame(json_decode(base64_decode($expected), true), json_decode($result[1], true));
    }

    /**
     * Retain the actual pinned original's positive query and file-helper contracts.
     *
     * @return array
     */
    public static function nativeCalleeLifetimes(): array
    {
        return array(
            array('settings', 'between', 'eyJpdGVtcyI6W3siZmlyc3QiOjd9LHsic2Vjb25kIjo3fSx7ImluaGVyaXRlZCI6N31dLCJvdXRlcl9jYWxscyI6MCwiaW5uZXJfY2FsbHMiOjB9'),
            array('settings', 'row', 'eyJpdGVtcyI6W3siZmlyc3QiOjd9LHsic2Vjb25kIjo3fSx7ImluaGVyaXRlZCI6N31dLCJvdXRlcl9jYWxscyI6MCwiaW5uZXJfY2FsbHMiOjB9'),
            array('settings', 'no-row', 'eyJpdGVtcyI6W3siZmlyc3QiOm51bGx9LHsic2Vjb25kIjoib3V0ZXItaG9vayJ9LHsiaW5oZXJpdGVkIjoib3V0ZXItaG9vayJ9XSwib3V0ZXJfY2FsbHMiOjIsImlubmVyX2NhbGxzIjoyfQ=='),
            array('settings', 'row-throws', 'eyJpdGVtcyI6W3siZmlyc3QiOiJvd25lZC1yb3ctdGhyb3cifSx7InNlY29uZCI6N30seyJpbmhlcml0ZWQiOjd9XSwib3V0ZXJfY2FsbHMiOjAsImlubmVyX2NhbGxzIjowfQ=='),
            array('settings', 'num-rows', 'eyJpdGVtcyI6W3siZmlyc3QiOiJvdXRlci1ob29rIn0seyJzZWNvbmQiOiJvdXRlci1ob29rIn0seyJpbmhlcml0ZWQiOiJvdXRlci1ob29rIn1dLCJvdXRlcl9jYWxscyI6MywiaW5uZXJfY2FsbHMiOjN9'),
            array('settings', 'inner-registers-outer', 'eyJpdGVtcyI6W3siZmlyc3QiOjd9LHsic2Vjb25kIjo3fSx7ImluaGVyaXRlZCI6N31dLCJvdXRlcl9jYWxscyI6MCwiaW5uZXJfY2FsbHMiOjN9'),
            array('settings', 'outer-only-row-inner', 'eyJpdGVtcyI6W3siZmlyc3QiOiJvdXRlci1ob29rIn0seyJzZWNvbmQiOiJvdXRlci1ob29rIn0seyJpbmhlcml0ZWQiOiJvdXRlci1ob29rIn1dLCJvdXRlcl9jYWxscyI6MywiaW5uZXJfY2FsbHMiOjB9'),
            array('settings', 'child-first', 'eyJpdGVtcyI6W3siZmlyc3QiOjd9LHsic2Vjb25kIjo3fSx7ImluaGVyaXRlZCI6N31dLCJvdXRlcl9jYWxscyI6MCwiaW5uZXJfY2FsbHMiOjB9'),
            array('settings', 'recursive', 'eyJpdGVtcyI6W3sicmVjdXJzaXZlIjo3fSx7ImZpcnN0Ijo3fSx7InNlY29uZCI6N30seyJpbmhlcml0ZWQiOjd9XSwib3V0ZXJfY2FsbHMiOjAsImlubmVyX2NhbGxzIjowfQ=='),
            array('settings', 'preexisting', 'eyJpdGVtcyI6W3siZmlyc3QiOiJvdXRlci1ob29rIn0seyJzZWNvbmQiOiJvdXRlci1ob29rIn0seyJpbmhlcml0ZWQiOiJvdXRlci1ob29rIn1dLCJvdXRlcl9jYWxscyI6MywiaW5uZXJfY2FsbHMiOjN9'),
            array('cache', 'between', 'eyJpdGVtcyI6W3siZmlyc3QiOjd9LHsic2Vjb25kIjo3fV0sIm91dGVyX2NhbGxzIjowLCJpbm5lcl9jYWxscyI6MH0='),
            array('cache', 'read-registers', 'eyJpdGVtcyI6W3siZmlyc3QiOiJvdXRlci1ob29rIn0seyJzZWNvbmQiOiJvdXRlci1ob29rIn1dLCJvdXRlcl9jYWxscyI6MiwiaW5uZXJfY2FsbHMiOjB9'),
            array('cache', 'read-missing', 'eyJpdGVtcyI6W3siZmlyc3QiOmZhbHNlfSx7InNlY29uZCI6Im91dGVyLWhvb2sifV0sIm91dGVyX2NhbGxzIjoxLCJpbm5lcl9jYWxscyI6MH0='),
            array('cache', 'preexisting', 'eyJpdGVtcyI6W3siZmlyc3QiOiJvdXRlci1ob29rIn0seyJzZWNvbmQiOiJvdXRlci1ob29rIn1dLCJvdXRlcl9jYWxscyI6MiwiaW5uZXJfY2FsbHMiOjB9')
        );
    }

    /**
     * Coerce native parser inputs once after preserving original host hook dispatch.
     *
     * @dataProvider decodedStringableValues
     * @param string $route
     * @param string $kind
     * @param string|null $expected
     * @return void
     */
    public function testNativeDecodedStringable($route, $kind, $expected): void
    {
        require_once LOW_OPTIONS_ROOT . '/tests/Support/NativeProcess.php';
        $result = NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/decoded-stringable-probe.php', array($route, $kind));
        self::assertSame(0, $result[0], $result[2] . $result[1]);
        self::assertSame('', $result[2]);
        self::assertSame(array('wire' => $expected, 'calls' => 1, 'same_exception' => $kind === 'throw', 'carrier_same' => true), json_decode($result[1], true));
    }

    /**
     * Include both public parser callers, legacy escaped text, native roots and throws.
     *
     * @return array
     */
    public static function decodedStringableValues(): array
    {
        $rows = array();
        foreach (array('settings', 'cache') as $route) {
            $rows[] = array($route, 'array', serialize(array('keep' => 7)));
            $rows[] = array($route, 'legacy-S', serialize('abc'));
            $rows[] = array($route, 'prefix', serialize(array('keep' => 7)));
            $rows[] = array($route, 'object', serialize((object) array('value' => 'native')));
            $rows[] = array($route, 'throw', null);
        }
        return $rows;
    }

    /**
     * Preserve raw base64 hook input before native-only escaped string handling.
     *
     * @return void
     */
    public function testOriginalBase64HookBeforeNativeParser(): void
    {
        require_once LOW_OPTIONS_ROOT . '/tests/Support/NativeProcess.php';
        $result = NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/settings-base64-hook-probe.php');
        self::assertSame(0, $result[0], $result[2] . $result[1]);
        self::assertSame('', $result[2]);
        self::assertSame(array('value' => 'abc', 'raw_object' => true, 'support_calls' => 0), json_decode($result[1], true));
    }

    /**
     * Preserve raw carrier values and decoded bytes passed to existing host hooks.
     *
     * @return void
     */
    public function testOriginalSettingsHookInputs(): void
    {
        require_once LOW_OPTIONS_ROOT . '/tests/Support/NativeProcess.php';
        $result = NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/settings-hook-inputs-probe.php');
        self::assertSame(0, $result[0], $result[2] . $result[1]);
        self::assertSame('', $result[2]);
        $rows = json_decode($result[1], true);
        foreach (array('raw-string', 'raw-null', 'raw-object', 'decoded-array', 'decoded-null', 'decoded-stringable') as $name) {
            self::assertSame(array('value' => array('value' => 'original-hook')), $rows[$name]['result']);
            self::assertSame(array(), $rows[$name]['events']);
        }
        self::assertSame(array('base64', 'NULL', null), $rows['raw-null']['inputs'][0]);
        self::assertSame(array('base64', 'object', array('carrier' => 1)), $rows['raw-object']['inputs'][0]);
        self::assertSame(array('unserialize', 'string', 'S:3:"abc";'), $rows['raw-string']['inputs'][1]);
        self::assertSame(array('unserialize', 'array', array('native' => 'wire')), $rows['decoded-array']['inputs'][1]);
        self::assertSame(array('unserialize', 'NULL', null), $rows['decoded-null']['inputs'][1]);
        self::assertSame(array('unserialize', 'object', array('wire' => 'S:3:"abc";')), $rows['decoded-stringable']['inputs'][1]);
        self::assertSame(0, $rows['decoded-stringable']['conversions']);
        self::assertSame(array('value' => array('value' => 'original-hook')), $rows['cache']['result']);
        self::assertSame(array(array('unserialize', 'string', 'S:3:"abc";')), $rows['cache']['inputs']);
        self::assertTrue($rows['cache']['writable_paths_match']);
    }

    /**
     * Preserve quiet native integer spellings and extension of stored strings.
     *
     * @dataProvider writableStringOffsets
     * @param int|string $key
     * @param string $expected
     * @return void
     */
    public function testWritableStringOffsetConversion($key, $expected): void
    {
        $this->ee->db->num_rows = 1;
        $this->ee->db->record = array('settings' => base64_encode(serialize('abc')));
        NativeOffsetText::$calls = 0;
        NativeOffsetText::$failure = null;
        self::assertTrue((new GlobalFieldSettings())->updateSetting($key, new NativeOffsetText()));
        $writes = array_values(array_filter($this->ee->db->calls, function ($call) { return $call[0] === 'update'; }));
        $written = $writes[0][1][1]['settings'];
        self::assertSame(serialize($expected), base64_decode($written));
        self::assertSame(1, NativeOffsetText::$calls);
    }

    /**
     * Include signed, padded and leading-whitespace integers accepted by PHP74.
     *
     * @return array
     */
    public static function writableStringOffsets(): array
    {
        return array(array(0, 'lbc'), array(-1, 'abl'), array(-3, 'lbc'), array(3, 'abcl'), array(5, 'abc  l'),
            array('1', 'alc'), array('01', 'alc'), array('+1', 'alc'), array(' 1', 'alc'), array('-1', 'abl'));
    }

    /**
     * Retain readable updater extensions and quiet optional-file fallback.
     *
     * @dataProvider optionalOverrides
     * @param string $state
     * @return void
     */
    public function testOptionalUpdaterExtension($state): void
    {
        require_once LOW_OPTIONS_ROOT . '/tests/Support/NativeProcess.php';
        $result = NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/optional-updater-probe.php', [$state]);
        self::assertSame(0, $result[0], $result[2] . $result[1]);
        self::assertSame('', $result[2]);
        self::assertSame(array('updated' => true, 'extension_calls' => $state === 'readable' ? 1 : 0), json_decode($result[1], true));
    }

    /**
     * Preserve native scalar, resource and object byte conversion exactly once.
     *
     * @dataProvider nativeOffsetValues
     * @param string $label
     * @param string $expected
     * @return void
     */
    public function testNativeNonstringOffsetValues($label, $expected): void
    {
        $values = array('integer' => 100, 'negative' => -100, 'float' => 12.5, 'infinity' => INF,
            'negative-infinity' => -INF, 'nan' => NAN, 'true' => true, 'stringable' => new NativeOffsetText());
        $resource = in_array($label, array('resource', 'closed-resource'), true) ? tmpfile() : null;
        if ($label === 'closed-resource') {
            fclose($resource);
        }
        $value = $resource === null ? $values[$label] : $resource;
        NativeOffsetText::$calls = 0;
        NativeOffsetText::$failure = null;
        $this->ee->db->num_rows = 1;
        $this->ee->db->record = array('settings' => base64_encode(serialize('abc')));
        try {
            self::assertTrue((new GlobalFieldSettings())->updateSetting(1, $value));
            $writes = array_values(array_filter($this->ee->db->calls, function ($call) { return $call[0] === 'update'; }));
            self::assertSame(serialize($expected), base64_decode($writes[0][1][1]['settings']));
            self::assertSame($label === 'stringable' ? 1 : 0, NativeOffsetText::$calls);
        } finally {
            if (is_resource($resource)) {
                fclose($resource);
            }
        }
    }

    /**
     * Include native quiet PHP74 coercions rather than originally invalid values.
     *
     * @return array
     */
    public static function nativeOffsetValues(): array
    {
        return array(array('integer', 'a1c'), array('negative', 'a-c'), array('float', 'a1c'), array('infinity', 'aIc'),
            array('negative-infinity', 'a-c'), array('nan', 'aNc'), array('true', 'a1c'), array('resource', 'aRc'),
            array('closed-resource', 'aRc'), array('stringable', 'alc'));
    }

    /**
     * Preserve a stringable callback exception before any database write.
     *
     * @return void
     */
    public function testNativeOffsetException(): void
    {
        NativeOffsetText::$calls = 0;
        $error = new \RuntimeException('owned native string conversion');
        NativeOffsetText::$failure = $error;
        $this->ee->db->num_rows = 1;
        $this->ee->db->record = array('settings' => base64_encode(serialize('abc')));
        try {
            (new GlobalFieldSettings())->updateSetting(1, new NativeOffsetText());
            self::fail('The native string conversion exception must propagate.');
        } catch (\RuntimeException $actual) {
            self::assertSame($error, $actual);
        } finally {
            NativeOffsetText::$failure = null;
        }
        self::assertSame(1, NativeOffsetText::$calls);
        self::assertSame(array(), array_values(array_filter($this->ee->db->calls, function ($call) { return $call[0] === 'update'; })));
    }

    /**
     * Preserve the native empty-string array key for null reads and writes.
     *
     * @return void
     */
    public function testNullSettingArrayKey(): void
    {
        $this->ee->db->num_rows = 1;
        $this->ee->db->record = array('settings' => base64_encode(serialize(array('' => 'native', 'keep' => 'retained'))));
        $settings = new GlobalFieldSettings();
        self::assertSame('native', $settings->getSetting(null));
        self::assertTrue($settings->updateSetting(null, 'changed'));
        $writes = array_values(array_filter($this->ee->db->calls, function ($call) { return $call[0] === 'update'; }));
        self::assertSame(serialize(array('' => 'changed', 'keep' => 'retained')), base64_decode($writes[0][1][1]['settings']));
    }

    /**
     * Retain legal unrelated namespace functions and platform constants.
     *
     * @return void
     */
    public function testNativeSettingsFunctionResolution(): void
    {
        require_once LOW_OPTIONS_ROOT . '/tests/Support/NativeProcess.php';
        $result = NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/settings-native-resolution.php');
        self::assertSame(0, $result[0], $result[2] . $result[1]);
        self::assertSame('', $result[2]);
        self::assertSame(array('read' => 'native', 'saved' => true, 'wire' => serialize(array(1000 => 'changed', 'keep' => 'retained')), 'legacy' => 'abc', 'support_calls' => 0, 'helper_calls' => 0), json_decode($result[1], true));
    }

    /**
     * Preserve legal child methods without adding parent helper declarations.
     *
     * @dataProvider existingCacheMethods
     * @param string $method
     * @param string $visibility
     * @param string $static
     * @return void
     */
    public function testExistingCacheSubclassMethods($method, $visibility, $static): void
    {
        require_once LOW_OPTIONS_ROOT . '/tests/Support/NativeProcess.php';
        $result = NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/cache-subclass-probe.php', array(LOW_OPTIONS_ROOT . '/low_options/low_options.phar', $method, $visibility, $static));
        self::assertSame(0, $result[0], $result[2] . $result[1]);
        self::assertSame('', $result[2]);
        self::assertSame(array('value' => 'existing child method'), json_decode($result[1], true));
    }

    /**
     * Keep public, protected and private static and instance child methods legal.
     *
     * @return array
     */
    public static function existingCacheMethods(): array
    {
        $cases = array();
        foreach (array('createDirectory', 'isWritableDirectory') as $method) {
            foreach (array('public', 'protected', 'private') as $visibility) {
                foreach (array('static', 'instance') as $static) {
                    $cases[] = array($method, $visibility, $static);
                }
            }
        }
        return $cases;
    }

    /**
     * Keep legal child method names available in the legacy extensible settings class.
     *
     * @return void
     */
    public function testExistingSettingsSubclassMethod(): void
    {
        require_once LOW_OPTIONS_ROOT . '/tests/Support/NativeProcess.php';
        $result = NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/settings-subclass-probe.php');
        self::assertSame(0, $result[0], $result[2] . $result[1]);
        self::assertSame('', $result[2]);
        self::assertSame(['value' => 'existing child method'], json_decode($result[1], true));
    }

    /**
     * Restore the explicit caller directory after unlinking a stale extraction root.
     *
     * @dataProvider cleanupDirectories
     * @param string $kind
     * @return void
     */
    public function testCleanupRestoresSuppliedDirectory($kind): void
    {
        require_once LOW_OPTIONS_ROOT . '/tests/Support/NativeProcess.php';
        $result = NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/cleanup-directory-probe.php', [$kind]);
        self::assertSame(0, $result[0], $result[2] . $result[1]);
        self::assertSame('', $result[2]);
        self::assertSame(['restored' => true, 'removed' => true, 'retained' => 'unchanged'], json_decode($result[1], true));
    }

    /**
     * Cover existing, dangling and separator-suffixed root links.
     *
     * @return array
     */
    public function cleanupDirectories(): array
    {
        return [['existing'], ['dangling'], ['trailing']];
    }

    /**
     * Load the Foundation fallback when its optional override cannot be read.
     *
     * @dataProvider optionalOverrides
     * @param string $state
     * @return void
     */
    public function testOptionalNavigationOverride($state): void
    {
        require_once LOW_OPTIONS_ROOT . '/tests/Support/NativeProcess.php';
        $result = NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/optional-nav-probe.php', [$state]);
        self::assertSame(0, $result[0], $result[2] . $result[1]);
        self::assertSame('', $result[2]);
        self::assertSame(['loaded' => true, 'override' => $state === 'readable'], json_decode($result[1], true));
    }

    /**
     * Include the normal extension and both optional fallback boundaries.
     *
     * @return array
     */
    public function optionalOverrides(): array
    {
        return [['readable'], ['missing'], ['unreadable']];
    }

    /**
     * Preserve native float array-key reads and writes without conversion warnings.
     *
     * @dataProvider floatSettingKeys
     * @param float $key
     * @param int $integer
     * @return void
     */
    public function testNativeFloatSettingKeys($key, $integer): void
    {
        $stored = array($integer => 'native', 'keep' => 'retained');
        $this->ee->db->num_rows = 1;
        $this->ee->db->record = array('settings' => base64_encode(serialize($stored)));
        $settings = new GlobalFieldSettings();
        self::assertSame('native', $settings->getSetting($key));
        self::assertSame(serialize($stored), serialize($settings->getSettings()));
        self::assertTrue($settings->updateSetting($key, 'changed'));
        $writes = array_values(array_filter($this->ee->db->calls, function ($call) { return $call[0] === 'update'; }));
        $written = $writes[0][1][1]['settings'];
        self::assertSame(serialize(array($integer => 'changed', 'keep' => 'retained')), base64_decode($written));
    }

    /**
     * Include native fractional, nonfinite and integer-width wrapping keys.
     *
     * @return array
     */
    public static function floatSettingKeys(): array
    {
        $width = 2.0 ** (PHP_INT_SIZE * 8);
        return array(
            array(1.9, 1), array(-1.9, -1), array(INF, 0), array(-INF, 0), array(NAN, 0),
            array(1e100, 0), array(-1e100, 0), array($width / 2, PHP_INT_MIN),
            array($width + 4096.0, 4096), array(-$width - 4096.0, -4096),
        );
    }

    /**
     * Keep the native first byte when writing a longer string to a stored offset.
     *
     * @dataProvider stringOffsetValues
     * @param string $value
     * @param string $expected
     * @return void
     */
    public function testNativeStringOffsetWrite($value, $expected): void
    {
        $stored = 'abc';
        $this->ee->db->num_rows = 1;
        $this->ee->db->record = array('settings' => base64_encode(serialize($stored)));
        self::assertTrue((new GlobalFieldSettings())->updateSetting(1, $value));
        $writes = array_values(array_filter($this->ee->db->calls, function ($call) { return $call[0] === 'update'; }));
        $written = $writes[0][1][1]['settings'];
        self::assertSame(serialize($expected), base64_decode($written));
    }

    /**
     * Retain byte semantics for ordinary text, UTF-8 and embedded null bytes.
     *
     * @return array
     */
    public static function stringOffsetValues(): array
    {
        return array(array('long', 'alc'), array("\xc3\xa9", "a\xc3c"), array("\0tail", "a\0c"));
    }

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
        return array(
            array('S:9:"bad";'), array('S:1:"\zz";'), array('s:9:"bad S:";'), array('!S:'), array('C:1:"X":-999:{S:}'),
            array('S:999999999999999999999999999999:"x";'),
            array('s:999999999999999999999999999999:"x";'),
            array('O:999999999999999999999999999999:"X":0:{}'),
            array('E:999999999999999999999999999999:"X:Y";'),
            array('C:999999999999999999999999999999:"X":0:{}'),
            array('C:1:"X":999999999999999999999999999999:{S:}'),
            array('C:1:"X":-999999999999999999999999999999:{S:}'),
        );
    }

    /**
     * Keep native zero padding and signed custom lengths within the byte bound.
     *
     * @return void
     */
    public function testPaddedNativeLengths(): void
    {
        $zeros = str_repeat('0', 40);
        $stored = 'a:2:{i:0;s:' . $zeros . '1:"B";i:1;S:' . $zeros . '1:"\41";}';
        self::assertSame(array('B', 'A'), SerializedValue::decode($stored));
        $name = OpaqueSettingsPayload::class;
        foreach (array('+' . $zeros . '1' => 'X', '-' . $zeros => '') as $size => $payload) {
            $stored = 'a:2:{i:0;C:' . $zeros . strlen($name) . ':"' . $name . '":' . $size . ':{' . $payload . '}i:1;S:1:"\41";}';
            $decoded = SerializedValue::decode($stored);
            self::assertSame($payload, $decoded[0]->payload);
            self::assertSame('A', $decoded[1]);
        }
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
     * Read native string byte offsets and return null for invalid storage lookups.
     *
     * @dataProvider storedOffsets
     * @param mixed $stored
     * @param mixed $key
     * @param mixed $expected
     * @return void
     */
    public function testStoredOffsets($stored, $key, $expected): void
    {
        $this->ee->db->num_rows = 1;
        $this->ee->db->record = array('settings' => base64_encode(serialize($stored)));
        $settings = new GlobalFieldSettings();
        self::assertSame($expected, $settings->getSetting($key));
        self::assertSame($stored, $settings->getSettings());
    }

    /**
     * Include clean native offsets, malformed keys and non-indexable roots.
     *
     * @return array
     */
    public static function storedOffsets(): array
    {
        return array(
            array('abc', 0, 'a'), array('abc', '0', 'a'), array('abc', 1, 'b'), array('abc', -1, 'c'),
            array('abc', '01', 'b'), array('abc', '+1', 'b'), array('abc', ' 1', 'b'),
            array('abc', 'missing', null), array('abc', 99, null), array('abc', array(), null),
            array('abc', true, null), array('abc', 1.2, null), array(7, 'key', null), array(true, 'key', null),
            array(array(1 => 'value'), true, 'value'), array(array('' => 'value'), null, 'value'),
            array(array(1 => 'value'), 1.0, 'value'), array(array(1 => 'value'), 1.2, 'value'),
            array(array('key' => 1), array(), null), array(array('key' => null), 'key', null),
        );
    }

    /**
     * Preserve byte writes and native serialization for a stored string root.
     *
     * @return void
     */
    public function testStoredStringWrite(): void
    {
        $this->ee->db->num_rows = 1;
        $this->ee->db->record = array('settings' => base64_encode(serialize('abc')));
        $settings = new GlobalFieldSettings();
        self::assertTrue($settings->updateSetting(1, 'Z'));
        $writes = array_values(array_filter($this->ee->db->calls, function ($call) { return $call[0] === 'update'; }));
        self::assertSame(base64_encode(serialize('aZc')), $writes[0][1][1]['settings']);
        $this->ee->db->record = $writes[0][1][1];
        self::assertSame('aZc', $settings->getSettings());
        self::assertSame('Z', $settings->getSetting(1));
        $this->ee->db->record = array('settings' => base64_encode(serialize(new \stdClass())));
        self::assertNull($settings->getSetting('missing'));
        self::assertInstanceOf(\stdClass::class, $settings->getSettings());
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
     * Retain native integer clamps and other keys during subsequent settings writes.
     *
     * @dataProvider overflowValues
     * @param string $integer
     * @param int $expected
     * @return void
     */
    public function testOverflowSettingsRemainWritable($integer, $expected): void
    {
        $stored = 'a:2:{s:4:"keep";s:3:"yes";s:1:"x";i:' . $integer . ';}';
        $this->ee->db->num_rows = 1;
        $this->ee->db->record = array('settings' => base64_encode($stored));
        $settings = new GlobalFieldSettings();
        self::assertSame(array('keep' => 'yes', 'x' => $expected), $settings->getSettings());
        self::assertTrue($settings->updateSetting('next', 'written'));
        $writes = array_values(array_filter($this->ee->db->calls, function ($call) { return $call[0] === 'update'; }));
        self::assertCount(1, $writes);
        $this->ee->db->record = array('settings' => $writes[0][1][1]['settings']);
        self::assertSame(array('keep' => 'yes', 'x' => $expected, 'next' => 'written'), $settings->getSettings());
    }

    /**
     * Read native integer cache boundaries without rewriting their stored bytes.
     *
     * @dataProvider integerBoundaries
     * @param string $integer
     * @param int $expected
     * @return void
     */
    public function testOverflowCachePreservesNativeValue($integer, $expected): void
    {
        $stored = 'a:2:{s:4:"keep";s:3:"yes";s:1:"x";i:' . $integer . ';}';
        try {
            self::assertTrue(PersistentCache::set('native-clamp', array()));
            $file = PATH_CACHE . 'low_options/' . sha1('native-clamp');
            file_put_contents($file, $stored);
            StaticCache::clear();
            self::assertSame(array('keep' => 'yes', 'x' => $expected), PersistentCache::get('native-clamp'));
            self::assertSame($stored, file_get_contents($file));
            unlink($file);
            self::assertSame(array('keep' => 'yes', 'x' => $expected), PersistentCache::get('native-clamp'));
        } finally {
            PersistentCache::delete('native-clamp');
            rmdir(PATH_CACHE . 'low_options');
            rmdir(PATH_CACHE);
        }
    }

    /**
     * Include diagnostic-free native integer boundaries for raw cache storage.
     *
     * @return array
     */
    public static function integerBoundaries(): array
    {
        return array(
            'positive' => array((string) PHP_INT_MAX, PHP_INT_MAX),
            'negative' => array((string) PHP_INT_MIN, PHP_INT_MIN),
        );
    }

    /**
     * Include both ends of the native integer range.
     *
     * @return array
     */
    public static function overflowValues(): array
    {
        return array(
            'positive' => array('99999999999999999999999999999', PHP_INT_MAX),
            'negative' => array('-99999999999999999999999999999', PHP_INT_MIN),
        );
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
        $maximum = PHP_INT_MAX;
        $minimum = PHP_INT_MIN;
        $maximum_references = array('x' => &$maximum, 'y' => &$maximum);
        $minimum_references = array('x' => &$minimum, 'y' => &$minimum);
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
            array('i:99999999999999999999999999999;', PHP_INT_MAX),
            array('i:-99999999999999999999999999999;', PHP_INT_MIN),
            array('a:1:{s:1:"x";i:99999999999999999999999999999;}', array('x' => PHP_INT_MAX)),
            array('a:1:{s:1:"x";i:-99999999999999999999999999999;}', array('x' => PHP_INT_MIN)),
            array('a:1:{i:99999999999999999999999999999;i:7;}', array(PHP_INT_MAX => 7)),
            array('a:1:{i:-99999999999999999999999999999;i:7;}', array(PHP_INT_MIN => 7)),
            array('a:2:{s:1:"x";i:99999999999999999999999999999;s:1:"y";R:2;}', $maximum_references),
            array('a:2:{s:1:"x";i:-99999999999999999999999999999;s:1:"y";R:2;}', $minimum_references),
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

final class NativeOffsetText
{
    public static $calls = 0;
    public static $failure;

    /**
     * Retain the native stringable conversion count and callback exception.
     *
     * @return string
     * @throws \RuntimeException
     */
    public function __toString()
    {
        self::$calls++;
        if (self::$failure !== null) {
            throw self::$failure;
        }
        return 'long';
    }
}
