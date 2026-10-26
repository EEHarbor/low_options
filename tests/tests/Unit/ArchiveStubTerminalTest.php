<?php

namespace LowOptionsTests;

require_once LOW_OPTIONS_ROOT . '/tests/Support/NativeProcess.php';

class ArchiveStubTerminalTest extends TestCase
{
    /**
     * Preserve successful returns and explicit corrupt-entry termination messages.
     *
     * @dataProvider terminalProvider
     * @param string $context
     * @param string $expected
     * @return void
     */
    public function testNativeEntryTermination($context, $expected): void
    {
        $directives = array();
        $extension = getenv('LOW_OPTIONS_STUB_XDEBUG');
        if ($extension) {
            $directives = array('zend_extension=' . $extension, 'xdebug.mode=coverage');
        }
        $result = NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/stub-probe.php', array($context), $directives);
        self::assertSame(array(0, $expected, ''), $result);
    }

    /**
     * Compare valid, checksum-mismatch and size-mismatch control flow.
     *
     * @return array
     */
    public static function terminalProvider(): array
    {
        return array(
            'valid' => array('valid', 'RETURNED:body'),
            'checksum' => array('checksum', 'Invalid internal .phar file (checksum error)'),
            'size' => array('size', 'Invalid internal .phar file (size error 4 != 5)'),
        );
    }
}
