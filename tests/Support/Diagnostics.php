<?php

namespace LowOptionsTests;

/** Retain diagnostics before code can catch their exceptions. */
final class Diagnostics
{
    private static $handler;

    /**
     * Install the same authority before discovery, providers and native scripts.
     *
     * @return void
     * @throws \RuntimeException
     */
    public static function start()
    {
        if (self::$handler !== null) {
            return;
        }
        if (error_reporting() !== -1 || ini_get('display_errors') !== 'stderr' || ini_get('display_startup_errors') !== '1') {
            throw new \RuntimeException('Strict startup diagnostic settings are required.');
        }
        self::record(array('kind' => 'startup', 'pid' => getmypid(), 'runtime' => PHP_VERSION, 'binary' => PHP_BINARY, 'error_reporting' => error_reporting(), 'display_errors' => ini_get('display_errors'), 'display_startup_errors' => ini_get('display_startup_errors'), 'script' => $_SERVER['SCRIPT_FILENAME'] ?? '', 'archive_sha256' => hash_file('sha256', dirname(__DIR__, 2) . '/low_options/low_options.phar')));
        self::$handler = static function ($severity, $message, $file, $line) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $caller = $trace[0];
            $source_callsite = false;
            foreach ($trace as $frame) {
                if (($frame['file'] ?? null) === $file && ($frame['line'] ?? null) === $line) {
                    $source_callsite = true;
                }
            }
            self::record(array('kind' => 'diagnostic', 'pid' => getmypid(), 'severity' => $severity, 'message_base64' => base64_encode($message), 'file_base64' => base64_encode($file), 'line' => $line, 'caller_file' => $caller['file'] ?? null, 'caller_line' => $caller['line'] ?? null, 'source_callsite' => $source_callsite));
            throw new \ErrorException($message, 0, $severity, $file, $line);
        };
        set_error_handler(self::$handler);
    }

    /**
     * Reuse the authority when a test or native script installs its handler.
     *
     * @return \Closure
     */
    public static function handler()
    {
        return self::$handler;
    }

    /**
     * Flush one lossless record to the checked invocation's private journal.
     *
     * @param array $record
     * @return void
     */
    public static function record(array $record)
    {
        $path = getenv('LOW_OPTIONS_DIAGNOSTIC_JOURNAL');
        if (!$path) {
            return;
        }
        $info = lstat($path);
        if (!$info || ($info['mode'] & 0170000) !== 0100000 || $info['nlink'] !== 1) {
            fwrite(STDERR, "Invalid diagnostic journal.\n");
            exit(70);
        }
        $handle = fopen($path, 'ab');
        $actual = $handle ? fstat($handle) : null;
        if (!$actual || $actual['dev'] !== $info['dev'] || $actual['ino'] !== $info['ino'] || !flock($handle, LOCK_EX)) {
            fwrite(STDERR, "Diagnostic journal ownership changed.\n");
            exit(70);
        }
        $bytes = json_encode($record, JSON_THROW_ON_ERROR) . "\n";
        $offset = 0;
        while ($offset < strlen($bytes)) {
            $written = fwrite($handle, substr($bytes, $offset));
            if ($written === false || $written === 0) {
                fwrite(STDERR, "Incomplete diagnostic journal.\n");
                exit(70);
            }
            $offset += $written;
        }
        if (!fflush($handle)) {
            fwrite(STDERR, "Cannot flush diagnostic journal.\n");
            exit(70);
        }
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

Diagnostics::start();
