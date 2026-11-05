<?php

namespace LowOptionsTests;

require_once LOW_OPTIONS_ROOT . '/tests/Support/NativeProcess.php';

class NativeLoadTest extends TestCase
{
    /**
     * Run native compilation under the exact parent runtime and strict diagnostics.
     *
     * @param array $arguments
     * @param array $directives
     * @return array
     * @throws \RuntimeException
     */
    private function runProbe(array $arguments, array $directives = array())
    {
        return NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/native-probe.php', $arguments, $directives);
    }

    /**
     * Verify direct plugin access exits before loading framework.
     *
     * @return void
     */
    public function testDirectPluginAccessExitsBeforeLoadingFramework(): void
    {
        list($exit, $stdout, $stderr) = $this->runProbe(array('guard'));
        self::assertSame(0, $exit, $stderr);
        self::assertSame('No direct script access allowed', $stdout);
    }

    /**
     * Verify unrelated namespace is ignored.
     *
     * @return void
     */
    public function testUnrelatedNamespaceIsIgnored(): void
    {
        list($exit, $stdout, $stderr) = $this->runProbe(array('unrelated'));
        self::assertSame(0, $exit, $stderr);
        self::assertSame(array('loaded' => false), json_decode($stdout, true));
    }
}
