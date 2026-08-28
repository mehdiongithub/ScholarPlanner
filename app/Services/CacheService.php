<?php

namespace App\Services;

class CacheService {
    /**
     * Get target cache filepath from key hash
     */
    private static function getCachePath(string $key): string {
        $hash = md5($key);
        return __DIR__ . '/../../storage/cache/cache_' . $hash . '.json';
    }

    /**
     * Get cached item or run fallback and save
     */
    public static function get(string $key, callable $fallback, int $ttl = 3600) {
        // Bypass cache during testing mode to ensure assertions run on fresh database states
        if (defined('TESTING_MODE') && TESTING_MODE) {
            return $fallback();
        }

        $path = self::getCachePath($key);
        if (file_exists($path) && (time() - filemtime($path)) < $ttl) {
            $data = json_decode(@file_get_contents($path), true);
            if ($data !== null) {
                return $data;
            }
        }

        $result = $fallback();
        self::set($key, $result);
        return $result;
    }

    /**
     * Set a cached value
     */
    public static function set(string $key, $value): void {
        $path = self::getCachePath($key);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        @file_put_contents($path, json_encode($value));
    }

    /**
     * Delete a specific cache item
     */
    public static function forget(string $key): void {
        $path = self::getCachePath($key);
        if (file_exists($path)) {
            @unlink($path);
        }
    }

    /**
     * Clear all cached files on catalog modification events
     */
    public static function clear(): void {
        $dir = __DIR__ . '/../../storage/cache';
        if (is_dir($dir)) {
            $files = glob($dir . '/cache_*.json');
            if ($files) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
            }
        }
    }
}
