<?php

namespace LowOptionsTests;

final class NativeDirectoryBindingTest extends TestCase
{
    /**
     * Retain independent original mkdir selection, unused sites and PHPUnit restoration.
     *
     * @dataProvider directoryBindingCases
     * @param string $case
     * @param array $results
     * @param array $nativePaths
     * @param array $hookPaths
     * @return void
     */
    public function testOriginalDirectoryCallees($case, array $results, array $nativePaths, array $hookPaths): void
    {
        require_once LOW_OPTIONS_ROOT . '/tests/Support/NativeProcess.php';
        $arguments = array(LOW_OPTIONS_ROOT . '/low_options/low_options.phar', 'Low\\Options', $case,
            LOW_OPTIONS_ROOT . '/tests/Fixtures/ee-file-helper.php', 'low_options', LOW_OPTIONS_ROOT . '/tests/.phpunit/phpunit-9.phar');
        $result = NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/cache-mkdir-sites-probe.php', $arguments, array('pcre.jit=0'));
        self::assertSame(0, $result[0], $result[1] . $result[2]);
        self::assertSame('', $result[2]);
        $observed = json_decode($result[1], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($results, $observed['results']);
        self::assertSame($nativePaths, array_column($observed['native'], 0));
        self::assertSame($hookPaths, array_column($observed['hooks'], 0));
        foreach ($observed['native'] as $native) {
            self::assertSame(@error_reporting(), $native[1]);
        }
        foreach ($observed['hooks'] as $hook) {
            self::assertSame(0700, $hook[1]);
            self::assertSame(@error_reporting(), $hook[2]);
        }
        if ($case === 'parent') {
            self::assertSame(serialize('stored'), $observed['stored']);
        }
        self::assertCount(count($hookPaths), $observed['logs']);
        foreach ($observed['logs'] as $message) {
            self::assertStringContainsString('could not create', $message);
        }
    }

    /**
     * Include hooks registered between original opcodes and after skipped paths.
     *
     * @return array
     */
    public static function directoryBindingCases(): array
    {
        $base = 'ownedsite://root/cache/';
        $module = $base . 'low_options/';
        return array(
            'native parent stat unavailable' => array('parent', array(true), array($base, $module), array()),
            'between original sites' => array('late', array(false), array($base), array($module)),
            'unused site' => array('unused', array(true, false), array($module), array($base)),
            'static restoration' => array('snapshot', array(true, true), array($base, $module, $base, $module), array())
        );
    }
}
