<?php

use Low\Options\FluxCapacitor\Conduit\PersistentCache;
use Low\Options\FluxCapacitor\Conduit\StaticCache;

/** Verify the checked launcher rejects even caught raw-cache parser warnings. */
class RawRangeDiagnosticTest extends \LowOptionsTests\TestCase
{
    /**
     * Exercise the actual raw cache reader and leave rejection to its durable journal.
     *
     * @dataProvider ranges
     * @param string $integer
     * @return void
     */
    public function testRawRangeWarningIsRetained($integer): void
    {
        try {
            PersistentCache::set('owned-raw-range', array());
            file_put_contents(PATH_CACHE . 'low_options/' . sha1('owned-raw-range'), 'i:' . $integer . ';');
            StaticCache::clear();
            try {
                PersistentCache::get('owned-raw-range');
            } catch (\ErrorException $exception) {
            }
            self::assertTrue(true);
        } finally {
            PersistentCache::delete('owned-raw-range');
            rmdir(PATH_CACHE . 'low_options');
            rmdir(PATH_CACHE);
        }
    }

    /**
     * Include both diagnostic-producing native clamp directions.
     *
     * @return array
     */
    public static function ranges(): array
    {
        return array(array(str_repeat('9', 30)), array('-' . str_repeat('9', 30)));
    }
}
