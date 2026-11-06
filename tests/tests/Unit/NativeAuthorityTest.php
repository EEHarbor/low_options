<?php

namespace LowOptionsTests;

require_once LOW_OPTIONS_ROOT . '/tests/Support/NativeProcess.php';

final class NativeAuthorityTest extends TestCase
{
    /**
     * Keep journal authority through PHPUnit's static-property restoration.
     *
     * @backupStaticAttributes enabled
     * @return void
     */
    public function testHealthyNativeAuthority(): void
    {
        self::assertSame(getenv('LOW_OPTIONS_DIAGNOSTIC_JOURNAL'), Diagnostics::journalPath());
        self::assertSame(array(0, "native\n", ''), NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/authority-probe.php', array('healthy')));
    }

    /**
     * Join both levels of a native invocation without losing stream bytes.
     *
     * @return void
     */
    public function testNestedNativeAuthority(): void
    {
        self::assertSame(array(0, "native\n", ''), NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/authority-probe.php', array('nested')));
    }
}
