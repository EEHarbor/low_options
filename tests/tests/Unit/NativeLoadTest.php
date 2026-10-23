<?php

namespace LowOptionsTests;

class NativeLoadTest extends TestCase
{
    /**
     * Run native compilation under the exact parent runtime and strict diagnostics.
     *
     * @param array $arguments
     * @param array $directives
     * @return array
     */
    private function runProbe(array $arguments, array $directives = array())
    {
        $temporary = sys_get_temp_dir() . '/low-options-probe-' . bin2hex(random_bytes(8));
        mkdir($temporary, 0700);
        $command = array(PHP_BINARY, '-d', 'error_reporting=-1', '-d', 'display_errors=1', '-d', 'sys_temp_dir=' . $temporary);
        foreach ($directives as $directive) {
            $command[] = '-d';
            $command[] = $directive;
        }
        $command[] = LOW_OPTIONS_ROOT . '/tests/scripts/native-probe.php';
        $command = array_merge($command, $arguments);
        $environment = getenv();
        $environment['TMPDIR'] = $temporary;
        $process = proc_open($command, array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes, LOW_OPTIONS_ROOT, $environment);
        if (!is_resource($process)) {
            throw new \RuntimeException('Could not start native source probe.');
        }
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($temporary, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($temporary);
        return array($exit, $stdout, $stderr);
    }

    /**
     * Compile every shipped archive class in a fresh process.
     *
     * @dataProvider archiveSymbolProvider
     * @param string $symbol
     * @return void
     */
    public function testNativeArchiveClassLoadsWithoutDiagnostics($symbol): void
    {
        list($exit, $stdout, $stderr) = $this->runProbe(array('load', '7.5.0', $symbol));
        self::assertSame(0, $exit, $stderr . $stdout);
        self::assertSame('', $stderr);
        self::assertSame(array('loaded' => true), json_decode($stdout, true));
    }

    /**
     * Provide archive symbol vectors.
     *
     * @return array
     */
    public static function archiveSymbolProvider(): array
    {
        $phar = new \Phar(LOW_OPTIONS_ROOT . '/low_options/low_options.phar');
        $data = array();
        foreach (new \RecursiveIteratorIterator($phar) as $file) {
            $name = str_replace('phar://' . $phar->getPath() . '/', '', $file->getPathname());
            if (strpos($name, 'FluxCapacitor/') === 0 && substr($name, -4) === '.php') {
                $data[$name] = array(str_replace('/', '\\', substr($name, strlen('FluxCapacitor/'), -4)));
            }
        }
        return $data;
    }

    /**
     * Select each supported EE bridge in a separate interpreter.
     *
     * @dataProvider versionProvider
     * @param string $version
     * @return void
     */
    public function testNativeBridgeSelection($version): void
    {
        list($exit, $stdout, $stderr) = $this->runProbe(array('selection', $version, 'Bridge\\EE'));
        self::assertSame(0, $exit, $stderr . $stdout);
        $expected = substr($version, 0, 1);
        $expected = $expected === '7' ? '6' : $expected;
        self::assertSame(array('loaded' => true, 'parent' => 'Low\\Options\\FluxCapacitor\\Bridge\\EE' . $expected), json_decode($stdout, true));
    }

    /**
     * Provide version vectors.
     *
     * @return array
     */
    public static function versionProvider(): array
    {
        return array(array('2.11.9'), array('3.5.17'), array('4.3.8'), array('5.4.2'), array('6.4.17'), array('7.5.0'));
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

    /**
     * Verify archive self extracts when phar wrapper is unavailable.
     *
     * @return void
     */
    public function testArchiveSelfExtractsWhenPharWrapperIsUnavailable(): void
    {
        list($exit, $stdout, $stderr) = $this->runProbe(array('no-phar'));
        self::assertSame(0, $exit, $stderr . $stdout);
        self::assertSame(array('loaded' => true), json_decode($stdout, true));
    }

    /**
     * Verify loader works with opcache permission validation.
     *
     * @return void
     */
    public function testLoaderWorksWithOpcachePermissionValidation(): void
    {
        list($exit, $stdout, $stderr) = $this->runProbe(array('load'), array('opcache.enable_cli=1', 'opcache.validate_permission=1'));
        self::assertSame(0, $exit, $stderr . $stdout);
        self::assertSame(array('loaded' => true), json_decode($stdout, true));
    }
}
