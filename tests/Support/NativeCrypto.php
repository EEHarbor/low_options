<?php

namespace LowOptionsTests;

final class NativeCrypto
{
    /**
     * Keep OpenSSL's irreversible error queue inside a disposable interpreter.
     *
     * @param string $context
     * @param array $options
     * @return array
     * @throws \RuntimeException
     */
    public static function run($context, array $options = array())
    {
        $temporary = sys_get_temp_dir() . '/low-options-crypto-' . bin2hex(random_bytes(8));
        mkdir($temporary, 0700);
        $command = array(PHP_BINARY, '-d', 'error_reporting=-1', '-d', 'display_errors=1', '-d', 'sys_temp_dir=' . $temporary);
        $extension = getenv('LOW_OPTIONS_CRYPTO_XDEBUG');
        if ($extension) {
            $command = array_merge($command, array('-d', 'zend_extension=' . $extension, '-d', 'xdebug.mode=coverage'));
        }
        $command = array_merge($command, array(LOW_OPTIONS_ROOT . '/tests/scripts/crypto-probe.php', $context, json_encode($options, JSON_THROW_ON_ERROR)));
        $environment = getenv();
        $environment['TMPDIR'] = $temporary;
        $process = null;
        try {
            $process = proc_open($command, array(0 => array('pipe', 'r'), 1 => array('file', $temporary . '/stdout', 'w'), 2 => array('file', $temporary . '/stderr', 'w')), $pipes, LOW_OPTIONS_ROOT, $environment);
            if (!is_resource($process)) {
                throw new \RuntimeException('Could not start isolated crypto probe.');
            }
            fclose($pipes[0]);
            $deadline = hrtime(true) + 20000000000;
            do {
                if (hrtime(true) >= $deadline) {
                    throw new \RuntimeException('Isolated crypto probe exceeded its deadline.');
                }
                $status = proc_get_status($process);
                if (!$status['running']) {
                    $closed = proc_close($process);
                    $process = null;
                    $exit = $status['exitcode'] !== -1 ? $status['exitcode'] : $closed;
                    return array($exit, file_get_contents($temporary . '/stdout'), file_get_contents($temporary . '/stderr'));
                }
                usleep(10000);
            } while (true);
        } finally {
            if (is_resource($process)) {
                proc_terminate($process, 9);
                proc_close($process);
            }
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($temporary, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $file) {
                if ($file->isLink() || !$file->isDir()) {
                    unlink($file->getPathname());
                    continue;
                }
                rmdir($file->getPathname());
            }
            rmdir($temporary);
        }
    }
}
