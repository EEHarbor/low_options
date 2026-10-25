<?php

namespace @@@ADDON_NAMESPACE@@@\FluxCapacitor\Conduit;

use @@@ADDON_NAMESPACE@@@\FluxCapacitor\Conduit\StaticCache;

// Based on CI DB caching function
class PersistentCache
{
    private static $cache_path = PATH_CACHE;
    private static $module_cache_path = PATH_CACHE . '@@@MODULE@@@/';

    /**
     * Retrieve the data stored in the cache.
     * @param  string|array  $key  Either a string or array of items
     * @return mixed        Decoded value, including false-like values
     */
    public static function get($key)
    {
        $key = self::createKey($key);
        if (StaticCache::has($key, true)) {
            return StaticCache::get($key, true);
        }

        if (! self::checkPath()) {
            return false;
        }

        ee()->load->helper('file');

        if (false === ($cache_data = read_file(self::$module_cache_path . $key))) {
            ee()->load->library('logger');
            ee()->logger->developer('@@@MODULE_NAME@@@: Write Cache enabled but could not find cache for: ' . self::$module_cache_path . $key);
            return false;
        }

        // Store the cache data in the StaticCache so we don't have
        // to read it off the disk again during this execution loop.
        $cache_data = self::serveCacheData($cache_data);
        StaticCache::set($key, $cache_data, true);

        return $cache_data;
    }

     /**
     * Set the data to be stored in the cache.
     * @param  string|array  $key  Either a string or array of items
     * @return boolean             Indicates whether the data was able to be saved
     */
    public static function set($key, $value)
    {
        if (!self::checkPath()) {
            return false;
        }

        ee()->load->helper('file');

        $key = self::createKey($key);
        $cache_data = self::prepCacheData($value);

        if (write_file(self::$module_cache_path . $key, $cache_data) === false) {
            ee()->load->library('logger');
            ee()->logger->developer('@@@MODULE_NAME@@@: Write Cache enabled but could not write cache file: ' . self::$module_cache_path . $key);

            return false;
        }

        // Try to change the folder permissions to EE's default write mode.
        @chmod(self::$module_cache_path . $key, FILE_WRITE_MODE);

        // Store the cache data in the StaticCache so we don't have
        // to read it off the disk again during this execution loop.
        // We use $value here because we don't need it in file format.
        StaticCache::set($key, $value, true);

        return true;
    }

    /**
     * Delete the cached file for a specific key for this module.
     * @return bool  Indicates if the key was successfully deleted.
     */
    public static function delete($key)
    {
        if (!self::checkPath()) {
            return false;
        }

        $key = self::createKey($key);

        if (is_file(self::$module_cache_path . $key)) {
            @unlink(self::$module_cache_path . $key);
        }

        // Remove any StaticCache we have for this key as well.
        StaticCache::delete($key, true);

        return true;
    }

    /**
     * Find out if a specific key exists in the file cache.
     * @param  string|array  $key            Either a string or an array if items
     * @param  boolean       $key_processed  Whether the key has already be SHA1'd
     * @return boolean                       Whether the key exists in the cache.
     */
    public static function has($key, $key_processed = false)
    {
        $key = $key_processed ? $key : self::createKey($key);
        return is_file(self::$module_cache_path . $key);
    }

    /**
     * Delete all the cached files that this module may have created.
     * @return bool  Indicates if the cache path existed and we attempted to clear it.
     */
    public static function clear()
    {
        if (!self::checkPath()) {
            return false;
        }

        foreach (glob(self::$module_cache_path . '*') as $key) {
            if (is_file($key)) {
                @unlink($key);
            }

            // Remove any StaticCache we have for this key as well.
            // We don't want to clear the StaticCache globally as
            // we may only be looking to clear the persistent cache
            // and leave the other things in the StaticCache alone.
            StaticCache::delete(pathinfo($key, PATHINFO_FILENAME), true);
        }

        return true;
    }


    /************************************************************
    PRIVATE FUNCTIONS
    ************************************************************/

    /**
     * Create a SHA1 key from either a string or an array of pieces to
     * ensure that the key is unique.
     * @param  string|array  $key  Either a string or an array of items
     * @return string              SHA1 string
     */
    private static function createKey($key)
    {
        if (!is_array($key)) {
            $key = array($key);
        }

        return sha1(implode(':', array_filter($key)));
    }

    /**
     * Prep the data to be stored in the cache. Making this it's own
     * method so we can add to it if needed.
     * @param  array|object  $value Data to store in cache
     * @return string        Serialized string of values
     */
    private static function prepCacheData($value)
    {
        return serialize($value);
    }

    /**
     * Decode native stored cache values while handling malformed storage.
     *
     * @param mixed $value Native file-reader result.
     * @return mixed
     * @throws \Throwable
     */
    private static function serveCacheData($value)
    {
        $callee = new class {
            public static bool $parser;
        };
        if (!isset($callee::$parser)) {
            $callee::$parser = \function_exists(__NAMESPACE__ . '\\unserialize');
        }
        if ($callee::$parser) {
            return unserialize($value);
        }
        return SerializedValue::decode($value, false);
    }

    /**
     * Make sure the EE global cache folder and our module-specific folders
     * exist and are writable.
     *
     * @return bool Whether the folders exist and are writable.
     * @throws \Throwable When an unrelated diagnostic is rejected by the caller.
     */
    private static function checkPath()
    {
        $isWritableDirectory = static function ($path) {
            return \is_writable($path) && (\DIRECTORY_SEPARATOR === '\\' || \is_executable($path));
        };
        /**
         * Create a native directory after preserving its original caller binding.
         *
         * @param string $path
         * @param int $mode
         * @return bool
         * @throws \Throwable When an unrelated native callback fails.
         */
        $createDirectory = static function ($path, $mode) use ($isWritableDirectory) {
            $directory = \rtrim($path, \DIRECTORY_SEPARATOR === '\\' ? '/\\' : '/');
            $parent = \dirname($directory);
            if (!\is_dir($parent) || !$isWritableDirectory($parent)) {
                return false;
            }

            $mkdir_line = 0;
            $previous = null;
            $previous = \set_error_handler(static function ($severity, $message, $file, $line) use (&$mkdir_line, &$previous) {
                if ($severity === \E_WARNING && $file === __FILE__ && $line === $mkdir_line
                    && \preg_match('/^mkdir\(\): (?:Permission denied|File exists|Not a directory|No such file or directory|Too many levels of symbolic links|Read-only file system)$/D', $message)) {
                    return true;
                }
                return $previous ? \call_user_func_array($previous, \func_get_args()) : false;
            });

            try {
                // Keep native alias spelling and its trailing separator intact.
                $mkdir_line = __LINE__ + 1;
                return \mkdir($path, $mode);
            } finally {
                \restore_error_handler();
            }
        };

        // Add a trailing slash to the path if needed
        self::$cache_path = preg_replace("/(.+?)\/*$/", "\\1/", self::$cache_path);

        // If the generic cache folder doesn't exist, try to create it.
        if (! @is_dir(self::$cache_path)) {
            // Each original mkdir opcode keeps an independent first-call binding.
            $genericMkdir = new class {
                public static bool $hook;
            };
            if (!isset($genericMkdir::$hook)) {
                $genericMkdir::$hook = \function_exists(__NAMESPACE__ . '\\mkdir');
            }
            $mkdir = $genericMkdir::$hook ? __NAMESPACE__ . '\\mkdir' : $createDirectory;
            if (!@$mkdir(self::$cache_path, DIR_WRITE_MODE)) {
                ee()->load->library('logger');
                ee()->logger->developer('@@@MODULE_NAME@@@: Write Cache enabled but could not create cache folder: ' . self::$cache_path);
                return false;
            }

            // Try to change the folder permissions to EE's default write mode.
            @chmod(self::$cache_path, DIR_WRITE_MODE);
        }

        // Make sure the generic cache folder is writable.
        if (!is_writable(self::$cache_path) || (\DIRECTORY_SEPARATOR !== '\\' && !\is_executable(self::$cache_path))) {
            ee()->load->library('logger');
            ee()->logger->developer('@@@MODULE_NAME@@@: Write Cache enabled but cache path not writable: ' . self::$cache_path);
            return false;
        }

        // If the module-specific cache folder doesn't exist, try to create it.
        if (!@is_dir(self::$module_cache_path)) {
            // Each original mkdir opcode keeps an independent first-call binding.
            $moduleMkdir = new class {
                public static bool $hook;
            };
            if (!isset($moduleMkdir::$hook)) {
                $moduleMkdir::$hook = \function_exists(__NAMESPACE__ . '\\mkdir');
            }
            $mkdir = $moduleMkdir::$hook ? __NAMESPACE__ . '\\mkdir' : $createDirectory;
            if (!@$mkdir(self::$module_cache_path, DIR_WRITE_MODE)) {
                ee()->load->library('logger');
                ee()->logger->developer('@@@MODULE_NAME@@@: Write Cache enabled but could not create module cache folder: ' . self::$module_cache_path);
                return false;
            }

            // Try to change the folder permissions to EE's default write mode.
            @chmod(self::$module_cache_path, DIR_WRITE_MODE);
        }

        // Make sure the module-specific cache folder is writable.
        if (!is_writable(self::$module_cache_path) || (\DIRECTORY_SEPARATOR !== '\\' && !\is_executable(self::$module_cache_path))) {
            // If the path is wrong we'll turn off caching
            ee()->load->library('logger');
            ee()->logger->developer('@@@MODULE_NAME@@@: Write Cache enabled but module cache path not writable: ' . self::$module_cache_path);
            return false;
        }

        return true;
    }
}
