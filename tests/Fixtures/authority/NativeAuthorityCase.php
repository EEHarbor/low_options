<?php

namespace LowOptionsTests;

require_once LOW_OPTIONS_ROOT . '/tests/Support/NativeProcess.php';

final class NativeAuthorityCase extends TestCase
{
    /**
     * Leave a green inner result after an ignored native authority fault.
     *
     * @return void
     */
    public function testIgnoredNativeOutcome(): void
    {
        $mode = getenv('LOW_OPTIONS_AUTHORITY_CASE');
        $journal = getenv('LOW_OPTIONS_DIAGNOSTIC_JOURNAL');
        $replacement = null;
        try {
            if ($mode === 'missing') {
                putenv('LOW_OPTIONS_DIAGNOSTIC_JOURNAL');
            }
            if ($mode === 'switched') {
                $replacement = tempnam(sys_get_temp_dir(), 'options-journal-');
                putenv('LOW_OPTIONS_DIAGNOSTIC_JOURNAL=' . $replacement);
            }
            $program = in_array($mode, array('missing', 'switched'), true) ? 'healthy' : $mode;
            NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/authority-probe.php', array($program));
        } finally {
            putenv('LOW_OPTIONS_DIAGNOSTIC_JOURNAL=' . $journal);
            if ($replacement !== null) {
                unlink($replacement);
            }
        }
        self::assertTrue(true);
    }
}
