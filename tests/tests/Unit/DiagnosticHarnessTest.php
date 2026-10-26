<?php

namespace LowOptionsTests;

class DiagnosticHarnessTest extends TestCase
{
    /**
     * Prove that source diagnostics remain exceptions under @-style masking.
     *
     * @return void
     */
    public function testSuppressedSourceDeprecationsAreNotDiscarded(): void
    {
        $handler = set_error_handler(function () { return false; });
        restore_error_handler();
        $mask = error_reporting(0);
        try {
            $handler(E_USER_DEPRECATED, 'intentional harness probe', LOW_OPTIONS_ROOT . '/low_options/pi.low_options.php', 1);
            self::fail('The strict source diagnostic handler did not throw.');
        } catch (\ErrorException $error) {
            self::assertSame(E_USER_DEPRECATED, $error->getSeverity());
            self::assertSame('intentional harness probe', $error->getMessage());
        } finally {
            error_reporting($mask);
        }
    }
}
