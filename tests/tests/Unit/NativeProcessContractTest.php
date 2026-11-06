<?php

namespace LowOptionsTests;

require_once LOW_OPTIONS_ROOT . '/tests/Support/NativeProcess.php';

class NativeProcessContractTest extends TestCase
{
    /**
     * Drain large independent output streams without a two-pipe deadlock.
     *
     * @return void
     */
    public function testLargeNativeOutput(): void
    {
        $result = NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/stub-probe.php', array('output'));
        self::assertSame(array(0, str_repeat('A', 1048576), str_repeat('B', 1048576)), $result);
    }

    /**
     * Reject invalid deadlines before creating a child or temporary directory.
     *
     * @dataProvider invalidTimeoutProvider
     * @param float $timeout
     * @return void
     */
    public function testInvalidTimeout($timeout): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Native probe timeout must be positive.');
        NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/stub-probe.php', array('output'), array(), $timeout);
    }

    /**
     * Provide zero and negative duration boundaries.
     *
     * @return array
     */
    public static function invalidTimeoutProvider(): array
    {
        return array(array(0), array(-1));
    }

    /**
     * Kill only the stalled child and clean its reported owned directory.
     *
     * @return void
     */
    public function testTimeoutCleanup(): void
    {
        $marker = sys_get_temp_dir() . '/low-options-marker-' . bin2hex(random_bytes(8));
        try {
            try {
                NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/stub-probe.php', array('wait', $marker), array(), 1);
                self::fail('The stalled child exceeded the deadline without being stopped.');
            } catch (\RuntimeException $error) {
                self::assertSame('Native PHP probe exceeded its deadline.', $error->getMessage());
            }
            self::assertFileExists($marker);
            self::assertDirectoryDoesNotExist(file_get_contents($marker));
        } finally {
            if (is_file($marker)) {
                unlink($marker);
            }
        }
    }

    /**
     * Preserve strict suppressed diagnostics in the actual native boundary.
     *
     * @return void
     */
    public function testSuppressedDiagnosticRemainsVisible(): void
    {
        $result = NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/stub-probe.php', array('diagnostic'));
        self::assertNotSame(0, $result[0]);
        self::assertStringContainsString('native strict sentinel', $result[1] . $result[2]);
    }
}
