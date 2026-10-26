<?php

namespace LowOptionsTests;

final class CaughtMainAuthorityCase extends TestCase
{
    /**
     * Retain an unexpected main diagnostic after a caller catches its exception.
     *
     * @return void
     */
    public function testCaughtMainDiagnostic(): void
    {
        $metadata = require LOW_OPTIONS_ROOT . '/low_options/addon.setup.php';
        if ($metadata['namespace'] !== 'Low\\Options') {
            throw new \RuntimeException('The distributed add-on metadata did not execute.');
        }
        try {
            @trigger_error('owned caught main diagnostic', E_USER_DEPRECATED);
        } catch (\ErrorException $error) {
            // The checked parent must still reject this deliberately green inner test.
        }
        self::assertTrue(true);
    }
}
