<?php

namespace LowOptionsTests;

use Low\Options\FluxCapacitor\Conduit\PersistentCache;
use Low\Options\FluxCapacitor\Conduit\StaticCache;

class ArchiveCacheFilesystemTest extends TestCase
{
    /**
     * Return false and log when a writable cache directory cannot be searched.
     *
     * @dataProvider nonsearchablePaths
     * @param string $mode
     * @return void
     */
    public function testNonsearchableCacheDirectories($mode): void
    {
        require_once LOW_OPTIONS_ROOT . '/tests/Support/NativeProcess.php';
        $result = NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/cache-search-probe.php', array($mode));
        self::assertSame(0, $result[0], $result[2] . $result[1]);
        self::assertSame('', $result[2]);
        self::assertSame(array('writable' => true, 'searchable' => false, 'value' => false, 'logs' => 1, 'cached' => false), json_decode($result[1], true));
    }

    /**
     * Cover missing base, missing module and existing module directories.
     *
     * @return array
     */
    public static function nonsearchablePaths(): array
    {
        return array(array('base'), array('module'), array('existing-module'));
    }

    /**
     * Restore writable fixture paths before releasing request-local state.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        try {
            $this->removeCacheFixture();
        } finally {
            parent::tearDown();
        }
    }

    /**
     * Remove only this process's cache files and empty fixture directories.
     *
     * @return void
     */
    private function removeCacheFixture(): void
    {
        $path = rtrim(PATH_CACHE, '/');
        if (is_file($path)) {
            unlink($path);
            return;
        }
        if (!is_dir($path)) {
            return;
        }
        chmod($path, DIR_WRITE_MODE);
        $module = $path . '/low_options';
        if (is_file($module)) {
            unlink($module);
            rmdir($path);
            return;
        }
        if (is_dir($module)) {
            chmod($module, DIR_WRITE_MODE);
            foreach (glob($module . '/*') as $file) {
                if (is_dir($file)) {
                    rmdir($file);
                    continue;
                }
                unlink($file);
            }
            rmdir($module);
        }
        rmdir($path);
        clearstatcache();
    }

    /**
     * Create real writable directories for filesystem guard vectors.
     *
     * @return void
     */
    private function createCacheDirectories(): void
    {
        self::assertTrue(mkdir(PATH_CACHE, DIR_WRITE_MODE));
        self::assertTrue(mkdir(PATH_CACHE . 'low_options', DIR_WRITE_MODE));
    }

    /**
     * Stop cache operations at an unwritable base or module directory.
     *
     * @dataProvider unwritablePathProvider
     * @param string $part
     * @param string $operation
     * @return void
     */
    public function testUnwritablePathReturnsFalseAndLogs($part, $operation): void
    {
        $this->createCacheDirectories();
        $path = $part === 'base' ? PATH_CACHE : PATH_CACHE . 'low_options/';
        self::assertTrue(chmod($path, 0500));
        clearstatcache();
        self::assertFalse(is_writable($path), 'These permission vectors require a nonprivileged filesystem user.');
        $arguments = $operation === 'set' ? array('key', array(1)) : array('key');
        if ($operation === 'clear') {
            $arguments = array();
        }
        self::assertFalse(call_user_func_array(array(PersistentCache::class, $operation), $arguments));
        $description = $part === 'base' ? 'cache path' : 'module cache path';
        self::assertSame(array('Low Options: Write Cache enabled but ' . $description . ' not writable: ' . $path), $this->ee->logger->messages);
        self::assertSame(array(array('library', array('logger'))), $this->ee->load->calls);
    }

    /**
     * Provide every guarded cache operation for both directory levels.
     *
     * @return array
     */
    public static function unwritablePathProvider(): array
    {
        $vectors = array();
        foreach (array('base', 'module') as $part) {
            foreach (array('get', 'set', 'delete', 'clear') as $operation) {
                $vectors[$part . '-' . $operation] = array($part, $operation);
            }
        }
        return $vectors;
    }

    /**
     * Match EE file helper reads, empty writes, append mode and open failures.
     *
     * @return void
     */
    public function testFileHelperContract(): void
    {
        $this->createCacheDirectories();
        $file = PATH_CACHE . 'low_options/helper';
        self::assertFalse(read_file($file));
        self::assertTrue(write_file($file, ''));
        self::assertSame('', read_file($file));
        self::assertTrue(write_file($file, 'first'));
        self::assertTrue(write_file($file, '-second', 'ab'));
        self::assertSame('first-second', read_file($file));
        self::assertFalse(write_file(PATH_CACHE . 'low_options/', 'invalid'));
        self::assertFalse(write_file(PATH_CACHE . 'absent/file', 'invalid'));
    }

    /**
     * Report a file-open failure without seeding the request-local cache.
     *
     * @return void
     */
    public function testFailedFileWriteReturnsFalseAndLogs(): void
    {
        $this->createCacheDirectories();
        $file = PATH_CACHE . 'low_options/' . sha1('blocked');
        self::assertTrue(mkdir($file, DIR_WRITE_MODE));
        self::assertFalse(PersistentCache::set('blocked', array('value' => 1)));
        self::assertFalse(StaticCache::has(sha1('blocked'), true));
        self::assertSame(array('Low Options: Write Cache enabled but could not write cache file: ' . $file), $this->ee->logger->messages);
        self::assertTrue(is_dir($file));
    }

    /**
     * Preserve an unrelated subdirectory while removing regular cache files.
     *
     * @return void
     */
    public function testClearLeavesDirectoriesAlone(): void
    {
        $this->createCacheDirectories();
        $directory = PATH_CACHE . 'low_options/nested';
        self::assertTrue(mkdir($directory, DIR_WRITE_MODE));
        self::assertTrue(PersistentCache::set('first', array(1)));
        self::assertTrue(PersistentCache::clear());
        self::assertTrue(is_dir($directory));
        self::assertFalse(PersistentCache::has('first'));
        self::assertSame(array(), $this->ee->logger->messages);
    }

    /**
     * Clear an empty directory repeatedly without producing diagnostics.
     *
     * @return void
     */
    public function testClearEmptyCacheIsIdempotent(): void
    {
        self::assertTrue(PersistentCache::clear());
        self::assertTrue(PersistentCache::clear());
        self::assertSame(array(), glob(PATH_CACHE . 'low_options/*'));
        self::assertSame(array(), $this->ee->logger->messages);
    }

    /**
     * Honor the documented already-processed key argument.
     *
     * @return void
     */
    public function testHasAcceptsProcessedHashKey(): void
    {
        self::assertTrue(PersistentCache::set('key', array(1)));
        self::assertTrue(PersistentCache::has('key'));
        self::assertTrue(PersistentCache::has(sha1('key'), true));
    }

    /**
     * Handle a file blocking directory creation without a hidden diagnostic.
     *
     * @dataProvider blockedDirectoryProvider
     * @param string $part
     * @return void
     */
    public function testBlockedDirectoryCreationReturnsFalse($part): void
    {
        $file = rtrim(PATH_CACHE, '/');
        if ($part === 'module') {
            self::assertTrue(mkdir(PATH_CACHE, DIR_WRITE_MODE));
            $file = PATH_CACHE . 'low_options';
        }
        $contents = 'not a directory';
        self::assertSame(strlen($contents), file_put_contents($file, $contents));
        self::assertFalse(PersistentCache::set('key', array(1)));
    }

    /**
     * Provide both directory creation boundaries.
     *
     * @return array
     */
    public static function blockedDirectoryProvider(): array
    {
        return array(array('base'), array('module'));
    }

    /**
     * Retain a robustness expectation for corrupted on-disk serialized values.
     *
     * @return void
     */
    public function testCorruptCacheReturnsFalseWithoutDiagnostic(): void
    {
        $this->createCacheDirectories();
        $contents = 'not serialized';
        self::assertSame(strlen($contents), file_put_contents(PATH_CACHE . 'low_options/' . sha1('corrupt'), $contents));
        self::assertFalse(PersistentCache::get('corrupt'));
    }
}
