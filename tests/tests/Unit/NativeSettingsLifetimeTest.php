<?php

namespace LowOptionsTests;

final class NativeSettingsOffsetContainer extends \ArrayObject
{
    public static $reporting = array();

    /**
     * Observe the original offset callback reporting context.
     *
     * @param mixed $name
     * @return string
     */
    public function offsetGet($name): string
    {
        self::$reporting[] = error_reporting();
        return 'native-offset';
    }
}

final class NativeSettingsLifetimeTest extends TestCase
{
    /**
     * Retain quiet native NAN carrier bytes before the original parser hook.
     *
     * @return void
     */
    public function testNativeNanCarrierKeepsItsDecodedBytes(): void
    {
        require_once LOW_OPTIONS_ROOT . '/tests/Support/NativeProcess.php';
        $result = NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/settings-native-nan-probe.php', array(
            LOW_OPTIONS_ROOT . '/low_options/low_options.phar'
        ));
        self::assertSame(0, $result[0], $result[1] . $result[2]);
        self::assertSame('', $result[2]);
        self::assertSame(array('decoded' => 'NAM=', 'reporting' => @error_reporting()), json_decode($result[1], true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * Preserve the original reporting scope for custom offset reads.
     *
     * @return void
     */
    public function testOffsetCallbackKeepsOriginalReportingContext(): void
    {
        $this->ee->db->num_rows = 1;
        $this->ee->db->record = array('settings' => base64_encode(serialize(new NativeSettingsOffsetContainer())));
        $settings = new \Low\Options\FluxCapacitor\Conduit\GlobalFieldSettings();
        NativeSettingsOffsetContainer::$reporting = array();

        try {
            self::assertSame('native-offset', $settings->getSetting('computed'));
            self::assertSame(array(@error_reporting()), NativeSettingsOffsetContainer::$reporting);
        } finally {
            NativeSettingsOffsetContainer::$reporting = array();
        }
    }

    /**
     * Keep native conversion, release, hydration and exceptional query cleanup ordered.
     *
     * @dataProvider temporaryArguments
     * @param string $route
     * @param string $failure
     * @return void
     */
    public function testTemporaryArgumentsKeepNativeReleaseOrder($route, $failure): void
    {
        require_once LOW_OPTIONS_ROOT . '/tests/Support/NativeProcess.php';
        $result = NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/settings-temporary-argument-probe.php', array(
            LOW_OPTIONS_ROOT . '/low_options/low_options.phar', 'Low\\Options', $route, $failure
        ));
        self::assertSame(0, $result[0], $result[1] . $result[2]);
        self::assertSame('', $result[2]);
        $observed = json_decode($result[1], true, 512, JSON_THROW_ON_ERROR);
        $masked = @error_reporting();
        $events = array(array('convert', $masked), array('destroy', $masked));
        if ($failure === 'none') {
            $outcome = array('ready' => true);
            $events[] = array('hydrate', true, $masked);
        } else {
            $outcome = array('exception' => $failure === 'conversion' ? 'conversion' : 'destructor');
        }
        $events[] = array('query-destroy', -1);
        self::assertSame(array('outcome' => $outcome, 'destroyed' => true, 'events' => $events), $observed);
    }

    /**
     * Cover both native temporary operands and each ordinary callback failure.
     *
     * @return array
     */
    public static function temporaryArguments(): array
    {
        $cases = array();
        foreach (array('raw', 'decoded') as $route) {
            foreach (array('none', 'conversion', 'destructor', 'both') as $failure) {
                $cases[$route . ' ' . $failure] = array($route, $failure);
            }
        }
        return $cases;
    }

    /**
     * Keep native Stringable conversion inside the real parser frame.
     *
     * @return void
     */
    public function testDecodedCallbackKeepsNativeConversionFrame(): void
    {
        require_once LOW_OPTIONS_ROOT . '/tests/Support/NativeProcess.php';
        $result = NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/settings-native-frame-probe.php', array(
            LOW_OPTIONS_ROOT . '/low_options/low_options.phar', 'GlobalFieldSettings'
        ));
        self::assertSame(0, $result[0], $result[1] . $result[2]);
        self::assertSame('', $result[2]);
        self::assertSame(array('value' => array('kept' => 'native'), 'events' => array(
            array('frame' => 'unserialize', 'reporting' => @error_reporting())
        )), json_decode($result[1], true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * Retain original setter arguments until successful or throwing writes finish.
     *
     * @dataProvider databaseOutcomes
     * @param string $outcome
     * @return void
     */
    public function testReplacementSurvivesDatabaseUpdate($outcome): void
    {
        require_once LOW_OPTIONS_ROOT . '/tests/Support/NativeProcess.php';
        $result = NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/settings-temporary-replacement-probe.php', array(
            LOW_OPTIONS_ROOT . '/low_options/low_options.phar', 'Low\\Options', $outcome
        ));
        self::assertSame(0, $result[0], $result[1] . $result[2]);
        self::assertSame('', $result[2]);
        $expectedOutcome = $outcome === 'happy' ? array('value' => true) : array('exception_same' => true, 'exception' => 'RuntimeException');
        self::assertSame(array('outcome' => $expectedOutcome, 'events' => array('convert', array(
            0 => 'db-update', 'destroyed' => false, 'value' => 'alc'
        ), 'destroy'), 'destroyed' => true), json_decode($result[1], true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * Exercise both original database return paths without diagnostic exceptions.
     *
     * @return array
     */
    public static function databaseOutcomes(): array
    {
        return array(array('happy'), array('throw'));
    }

    /**
     * Preserve original opcode binding through actual pinned PHPUnit static restoration.
     *
     * @dataProvider staticRestorationPoints
     * @param string $route
     * @param string $when
     * @return void
     */
    public function testStaticRestorationCannotRebindNativeCallees($route, $when): void
    {
        require_once LOW_OPTIONS_ROOT . '/tests/Support/NativeProcess.php';
        $result = NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/settings-static-restore-probe.php', array(
            LOW_OPTIONS_ROOT . '/low_options/low_options.phar', $route, $when
        ));
        self::assertSame(0, $result[0], $result[1] . $result[2]);
        self::assertSame('', $result[2]);
        self::assertSame(array(7, 7, 7), json_decode($result[1], true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * Cover unloaded, loaded and separately initialized lexical reader state.
     *
     * @return array
     */
    public static function staticRestorationPoints(): array
    {
        return array(array('settings', 'before-source'), array('settings', 'after-source'),
            array('cache', 'after-source'), array('cache', 'after-other-reader'));
    }    /**
     * Keep the original public key available to legal serializer callbacks.
     *
     * @dataProvider rawKeyParameters
     * @param string $case
     * @param array $expected
     * @return void
     */
    public function testOriginalKeyParameter($case, array $expected): void
    {
        require_once LOW_OPTIONS_ROOT . '/tests/Support/NativeProcess.php';
        $result = NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/settings-raw-key-probe.php', array(
            LOW_OPTIONS_ROOT . '/low_options/low_options.phar', 'Low\\Options', $case
        ));
        self::assertSame(0, $result[0], $result[1] . $result[2]);
        self::assertSame('', $result[2]);
        self::assertSame(array($expected), json_decode($result[1], true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * Cover original fractional and null keys without replacing their parameters.
     *
     * @return array
     */
    public static function rawKeyParameters(): array
    {
        return array(array('fraction', array('double', 1.9)), array('null', array('NULL', null)));
    }

    /**
     * Preserve the original truthy NAN root without a new boolean conversion.
     *
     * @return void
     */
    public function testNativeNanSettingsRoot(): void
    {
        $this->ee->db->num_rows = 1;
        $this->ee->db->record = array('settings' => base64_encode('d:NAN;'));
        $settings = new \Low\Options\FluxCapacitor\Conduit\GlobalFieldSettings();
        $value = $settings->getSettings();
        self::assertIsFloat($value);
        self::assertTrue(is_nan($value));
    }

    /**
     * Keep raw ArrayAccess key and value temporaries in their original release order.
     *
     * @dataProvider arrayAccessWriteOutcomes
     * @param string $case
     * @return void
     */
    public function testRawArrayAccessArgumentLifetime($case): void
    {
        require_once LOW_OPTIONS_ROOT . '/tests/Support/NativeProcess.php';
        $result = NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/settings-aa-argument-lifetime-probe.php', array(
            LOW_OPTIONS_ROOT . '/low_options/low_options.phar', 'Low\\Options', $case
        ));
        self::assertSame(0, $result[0], $result[1] . $result[2]);
        self::assertSame('', $result[2]);
        $events = array('assign');
        if ($case !== 'assign-throws') {
            $events[] = 'db-update';
        }
        $events[] = 'key-destroy';
        $events[] = 'value-destroy';
        if ($case !== 'happy') {
            $events[] = 'caught-same';
        }
        self::assertSame($events, json_decode($result[1], true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * Retain success and both native callback-unwind boundaries.
     *
     * @return array
     */
    public static function arrayAccessWriteOutcomes(): array
    {
        return array(array('happy'), array('assign-throws'), array('db-throws'));
    }


}
