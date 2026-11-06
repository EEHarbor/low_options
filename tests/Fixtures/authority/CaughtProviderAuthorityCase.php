<?php

namespace LowOptionsTests;

final class CaughtProviderAuthorityCase extends TestCase
{
    /**
     * Complete a positive assertion after a deliberately faulty provider.
     *
     * @dataProvider caughtProvider
     * @param bool $value
     * @return void
     */
    public function testProviderResult($value): void
    {
        self::assertTrue($value);
    }

    /**
     * Retain an unexpected discovery diagnostic even when its exception is caught.
     *
     * @return array
     */
    public static function caughtProvider(): array
    {
        $metadata = require LOW_OPTIONS_ROOT . '/low_options/addon.setup.php';
        if ($metadata['namespace'] !== 'Low\\Options') {
            throw new \RuntimeException('The distributed add-on metadata did not execute.');
        }
        try {
            @trigger_error('owned caught provider diagnostic', E_USER_DEPRECATED);
        } catch (\ErrorException $error) {
            // Returning a dataset must not erase the discovery diagnostic receipt.
        }
        return array(array(true));
    }
}
