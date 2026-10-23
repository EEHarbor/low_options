<?php

namespace LowOptionsTests;

final class NativeProcess
{
    /**
     * Run native probes with isolated files, bounded lifetime and owned cleanup.
     *
     * @param string $script
     * @param array $arguments
     * @param array $directives
     * @param float $timeout
     * @return array
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public static function run($script, array $arguments = array(), array $directives = array(), $timeout = 20)
    {
        if ($timeout <= 0) {
            throw new \InvalidArgumentException('Native probe timeout must be positive.');
        }
        $temporary = sys_get_temp_dir() . '/low-options-process-' . bin2hex(random_bytes(8));
        mkdir($temporary, 0700);
        $command = array(PHP_BINARY, '-d', 'error_reporting=-1', '-d', 'display_errors=1', '-d', 'sys_temp_dir=' . $temporary);
        foreach ($directives as $directive) {
            $command[] = '-d';
            $command[] = $directive;
        }
        $command = array_merge($command, array($script), $arguments);
        $environment = getenv();
        $environment['TMPDIR'] = $temporary;
        $process = null;
        try {
            $process = proc_open($command, array(0 => array('pipe', 'r'), 1 => array('file', $temporary . '/stdout', 'w'), 2 => array('file', $temporary . '/stderr', 'w')), $pipes, LOW_OPTIONS_ROOT, $environment);
            if (!is_resource($process)) {
                throw new \RuntimeException('Could not start native PHP probe.');
            }
            fclose($pipes[0]);
            $deadline = hrtime(true) + ($timeout * 1000000000);
            do {
                if (hrtime(true) >= $deadline) {
                    throw new \RuntimeException('Native PHP probe exceeded its deadline.');
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
