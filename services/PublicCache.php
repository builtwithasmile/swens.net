<?php
declare(strict_types=1);

namespace App\Services;

/**
 * File-based full-page cache for public GET responses.
 * Hard law 6: a warm public request = zero DB queries.
 * Never caches /gate, /admin/*, or requests with a query string.
 * TTL: 24h. Atomic write via tmp file + rename().
 * Bypassed entirely in APP_ENV=development.
 */
class PublicCache
{
    private const TTL = 86400; // 24 hours

    public static function remember(string $path, callable $builder): string
    {
        if (self::bypass($path)) {
            return $builder();
        }
        $file = self::cacheFile($path);
        if (is_file($file) && (time() - filemtime($file)) < self::TTL) {
            return (string) file_get_contents($file);
        }
        $html = $builder();
        self::write($file, $html);
        return $html;
    }

    /** Purge all cached pages. Called after any admin write. */
    public static function purgeAll(): void
    {
        $dir = self::cacheDir();
        if (!is_dir($dir)) {
            return;
        }
        foreach (glob($dir . '/*.html') ?: [] as $f) {
            @unlink($f);
        }
    }

    private static function bypass(string $path): bool
    {
        if (defined('APP_ENV') && APP_ENV === 'development') {
            return true;
        }
        // Never cache gate or admin paths
        if (str_starts_with($path, '/gate') || str_starts_with($path, '/admin')) {
            return true;
        }
        // Never cache if query string present
        if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '') {
            return true;
        }
        // Only cache GET
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method !== 'GET') {
            return true;
        }
        return false;
    }

    private static function cacheFile(string $path): string
    {
        $clean = rtrim($path, '/') ?: '/';
        if ($clean === '/') {
            $key = '_home';
        } else {
            $key = ltrim(str_replace('/', '__', $clean), '_');
        }
        return self::cacheDir() . '/' . $key . '.html';
    }

    private static function cacheDir(): string
    {
        return APP_ROOT . '/storage/cache/pages';
    }

    private static function write(string $file, string $html): void
    {
        $dir = self::cacheDir();
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $tmp = $file . '.' . getmypid() . '.tmp';
        if (file_put_contents($tmp, $html) !== false) {
            rename($tmp, $file);
        }
    }
}
