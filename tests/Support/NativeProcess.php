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
        $command = array(PHP_BINARY, '-d', 'sys_temp_dir=' . $temporary);
        foreach ($directives as $directive) {
            $command[] = '-d';
            $command[] = $directive;
        }
        $command = array_merge($command, array('-d', 'error_reporting=-1', '-d', 'display_errors=stderr', '-d', 'display_startup_errors=1', '-d', 'log_errors=0', '-d', 'html_errors=0', '-d', 'auto_prepend_file=' . __DIR__ . '/Diagnostics.php', $script), $arguments);
        $invocation = bin2hex(random_bytes(8));
        Diagnostics::record(array('kind' => 'native-start', 'id' => $invocation, 'command' => $command, 'temporary' => $temporary));
        $completed = false;
        $spawned = false;
        $environment = getenv();
        $environment['TMPDIR'] = $temporary;
        $journal = Diagnostics::journalPath();
        if ($journal !== null) {
            $environment['LOW_OPTIONS_DIAGNOSTIC_JOURNAL'] = $journal;
            unset($environment['LOW_OPTIONS_NATIVE_OBSERVER']);
        }
        $process = null;
        $termination = null;
        try {
            $process = proc_open($command, array(0 => array('pipe', 'r'), 1 => array('file', $temporary . '/stdout', 'w'), 2 => array('file', $temporary . '/stderr', 'w')), $pipes, LOW_OPTIONS_ROOT, $environment);
            if (!is_resource($process)) {
                throw new \RuntimeException('Could not start native PHP probe.');
            }
            fclose($pipes[0]);
            $deadline = hrtime(true) + ($timeout * 1000000000);
            do {
                $status = proc_get_status($process);
                if (!$spawned) {
                    Diagnostics::record(array('kind' => 'native-spawn', 'id' => $invocation, 'pid' => $status['pid']));
                    $spawned = true;
                }
                if (!$status['running']) {
                    $closed = proc_close($process);
                    $process = null;
                    $exit = $status['exitcode'] !== -1 ? $status['exitcode'] : $closed;
                    Diagnostics::record(array('kind' => 'native-reaped', 'id' => $invocation, 'status' => $status, 'close_status' => $closed));
                    $stdout = file_get_contents($temporary . '/stdout');
                    $stderr = file_get_contents($temporary . '/stderr');
                    Diagnostics::record(array('kind' => 'native-end', 'id' => $invocation, 'exit' => $exit, 'pid' => $status['pid'], 'stdout_base64' => base64_encode($stdout), 'stderr_base64' => base64_encode($stderr)));
                    $completed = true;
                    return array($exit, $stdout, $stderr);
                }
                if (hrtime(true) >= $deadline) {
                    throw new \RuntimeException('Native PHP probe exceeded its deadline.');
                }
                usleep(10000);
            } while (true);
        } finally {
            if (is_resource($process)) {
                $requested = proc_terminate($process, 9);
                $termination_deadline = hrtime(true) + 2000000000;
                do {
                    $status = proc_get_status($process);
                    if (!$status['running'] || hrtime(true) >= $termination_deadline) {
                        break;
                    }
                    usleep(10000);
                } while (true);
                $closed = proc_close($process);
                $process = null;
                $termination = array('requested' => $requested, 'signal' => 9, 'status' => $status, 'close_status' => $closed);
                Diagnostics::record(array('kind' => 'native-termination', 'id' => $invocation) + $termination);
            }
            if (!$completed) {
                Diagnostics::record(array('kind' => 'native-aborted', 'id' => $invocation, 'termination' => $termination, 'stdout_base64' => is_file($temporary . '/stdout') ? base64_encode(file_get_contents($temporary . '/stdout')) : null, 'stderr_base64' => is_file($temporary . '/stderr') ? base64_encode(file_get_contents($temporary . '/stderr')) : null));
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
            Diagnostics::record(array('kind' => 'native-cleanup', 'id' => $invocation, 'completed' => $completed, 'removed' => !file_exists($temporary)));
        }
    }
}
