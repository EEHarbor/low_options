<?php

namespace LowOptionsTests;

class ArchiveCacheFilesystemTest extends TestCase
{
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
}
