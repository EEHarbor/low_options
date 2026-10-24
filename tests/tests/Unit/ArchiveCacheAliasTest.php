<?php

namespace LowOptionsTests;

use Low\Options\FluxCapacitor\Conduit\PersistentCache;
use Low\Options\FluxCapacitor\Conduit\StaticCache;

final class ArchiveCacheAliasTest extends TestCase
{
    private $root;
    private $originalPaths = array();

    /**
     * Retain the archive's configured paths before using an owned fixture root.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir() . '/low-options-alias-' . bin2hex(random_bytes(8));
        foreach (array('cache_path', 'module_cache_path') as $name) {
            $property = new \ReflectionProperty(PersistentCache::class, $name);
            if (PHP_VERSION_ID < 80100) {
                $property->setAccessible(true);
            }
            $this->originalPaths[$name] = $property->getValue();
        }
    }

    /**
     * Restore configured paths before releasing the request fixture.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        foreach ($this->originalPaths as $name => $value) {
            $this->property(PersistentCache::class, $name, $value);
        }
        parent::tearDown();
    }

    /**
     * Create native one-hop alias targets while rejecting unresolved paths quietly.
     *
     * @dataProvider cacheDirectoryAliases
     * @param string $location
     * @param string $kind
     * @param bool $expected
     * @return void
     */
    public function testCacheDirectoryAliases($location, $kind, $expected): void
    {
        mkdir($this->root, 0700);
        $cache = $this->root . '/cache/';
        if ($location === 'module') {
            mkdir($cache, 0700);
        }
        $alias = $location === 'base' ? rtrim($cache, '/') : $cache . 'low_options';
        $target = $this->root . '/target';
        $relative = $location === 'base' ? 'target' : '../target';
        if (strpos($kind, 'existing-') === 0) {
            mkdir($target, 0700);
        }
        if ($kind === 'existing-two-hop' || $kind === 'missing-two-hop') {
            symlink($target, $this->root . '/middle');
            $target_path = $this->root . '/middle';
        } else {
            $target_path = $kind === 'missing-absolute' ? $target : $relative;
        }
        if ($kind === 'missing-parent') {
            $target_path = $this->root . '/absent/target';
        }
        if ($kind === 'cycle') {
            $target_path = $alias;
        }
        $locked = null;
        if ($kind === 'file-target') {
            file_put_contents($target, 'occupied');
            $target_path = $target;
        }
        if ($kind === 'read-only-parent' || $kind === 'nonsearchable-parent') {
            $locked = $this->root . '/target-parent';
            mkdir($locked, 0700);
            $target_path = $locked . '/target';
            chmod($locked, $kind === 'read-only-parent' ? 0500 : 0200);
        }
        symlink($target_path, $alias);
        $this->property(PersistentCache::class, 'cache_path', $cache);
        $this->property(PersistentCache::class, 'module_cache_path', $cache . 'low_options/');
        $before = set_error_handler(static function () {});
        restore_error_handler();

        try {
            self::assertSame($expected, PersistentCache::set('alias', ['stored' => 'value']));
            $after = set_error_handler(static function () {});
            restore_error_handler();
            self::assertSame($before, $after);
            self::assertTrue(is_link($alias));
            self::assertCount($expected ? 0 : 1, $this->ee->logger->messages);
            if ($expected) {
                self::assertDirectoryExists($target);
                StaticCache::clear();
                self::assertSame(['stored' => 'value'], PersistentCache::get('alias'));
                self::assertTrue(PersistentCache::delete('alias'));
                self::assertFalse(PersistentCache::has('alias'));
            }
        } finally {
            if ($locked !== null) {
                chmod($locked, 0700);
            }
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $file) {
                if ($file->isLink() || !$file->isDir()) {
                    unlink($file->getPathname());
                } else {
                    rmdir($file->getPathname());
                }
            }
            rmdir($this->root);
        }
    }

    /**
     * Cover both configured alias positions and native successful target creation.
     *
     * @return array
     */
    public function cacheDirectoryAliases(): array
    {
        $cases = [];
        foreach (['base', 'module'] as $location) {
            foreach (['existing-relative', 'existing-two-hop', 'missing-relative', 'missing-absolute', 'missing-parent', 'cycle', 'missing-two-hop', 'file-target', 'read-only-parent', 'nonsearchable-parent'] as $kind) {
                $cases[] = [$location, $kind, in_array($kind, ['existing-relative', 'existing-two-hop', 'missing-relative', 'missing-absolute'], true)];
            }
        }
        return $cases;
    }

}
