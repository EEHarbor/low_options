<?php

namespace LowOptionsTests;

require_once LOW_OPTIONS_ROOT . '/tests/Support/LegacyState.php';

class ArchiveLegacyStateTest extends TestCase
{
    /**
     * Bind every mutation snapshot to its live owning builder.
     *
     * @return void
     */
    public function testDatabaseJournalOwnerBoundary(): void
    {
        $primary = $this->ee->db;
        $primary->where('pending', 17);
        $clone = clone $primary;
        $clone->where('site_id', 1)->update('owned_table', array('zero' => 0));
        $fresh = $primary->newQuery();
        $fresh->where('site_id', 2)->insert('other_owned_table', array('zero' => 0));
        $entries = $primary->sharedTrace->journal;
        self::assertSame(array('where', array('pending', 17)), $entries[0]['call']);
        self::assertSame(spl_object_id($clone), $entries[2]['owner']);
        self::assertSame(array(array('where', array('pending', 17)), array('where', array('site_id', 1)), array('update', array('owned_table', array('zero' => 0)))), $entries[2]['calls']);
        self::assertSame(spl_object_id($fresh), $entries[4]['owner']);
        self::assertSame(array(array('where', array('site_id', 2)), array('insert', array('other_owned_table', array('zero' => 0)))), $entries[4]['calls']);
        self::assertSame(array(array('where', array('pending', 17))), $primary->calls);
    }

    /**
     * Verify current EE's isset default separately from bridge behavior.
     *
     * @return void
     */
    public function testSessionFixtureDefaults(): void
    {
        $session = new LegacySession();
        $session->userdata = array('zero' => 0, 'false' => false, 'null' => null);
        self::assertSame(0, $session->userdata('zero', 'missing'));
        self::assertFalse($session->userdata('false', 'missing'));
        self::assertSame('missing', $session->userdata('null', 'missing'));
        self::assertSame('missing', $session->userdata('absent', 'missing'));
    }
}
