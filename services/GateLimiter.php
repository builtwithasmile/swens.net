<?php
declare(strict_types=1);

namespace App\Services;

/**
 * File-based rate limiter for the Gate request form.
 *
 * Per-IP:  storage/ratelimit/<sha1(ip)>.json  — array of unix timestamps
 *          pruned to the last hour on every read; deny when count >= GATE_MAX_PER_IP_PER_HOUR.
 * Global:  storage/ratelimit/global-YYYYMMDD.count — integer; deny when >= GATE_MAX_PER_DAY_GLOBAL.
 *
 * Best-effort LOCK_EX writes; a lost race costs one extra email, acceptable.
 * Stale-file cleanup: per-IP files older than 24h are unlinked on any allow() call.
 */
final class GateLimiter
{
    private string $dir;
    private int    $maxPerIpPerHour;
    private int    $maxPerDayGlobal;

    public function __construct(?string $storageDir = null)
    {
        $this->dir = $storageDir ?? (defined('APP_ROOT') ? APP_ROOT . '/storage/ratelimit' : sys_get_temp_dir() . '/swens_ratelimit');
        $this->maxPerIpPerHour = defined('GATE_MAX_PER_IP_PER_HOUR') ? (int) GATE_MAX_PER_IP_PER_HOUR : 3;
        $this->maxPerDayGlobal = defined('GATE_MAX_PER_DAY_GLOBAL')  ? (int) GATE_MAX_PER_DAY_GLOBAL  : 20;

        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0755, true);
        }
    }

    /**
     * Returns true if this request should be allowed; false if rate-limited.
     * On true: records the hit (both per-IP and global).
     * On false: does NOT record (idempotent).
     */
    public function allow(string $ip): bool
    {
        $this->pruneStale();

        if (!$this->allowedByIp($ip)) {
            return false;
        }
        if (!$this->allowedByGlobal()) {
            return false;
        }

        $this->recordHit($ip);
        return true;
    }

    // -------------------------------------------------------------------------

    private function allowedByIp(string $ip): bool
    {
        $file = $this->ipFile($ip);
        $now  = time();
        $hits = $this->readIpHits($file, $now);
        return count($hits) < $this->maxPerIpPerHour;
    }

    private function allowedByGlobal(): bool
    {
        $count = $this->readGlobalCount();
        return $count < $this->maxPerDayGlobal;
    }

    private function recordHit(string $ip): void
    {
        $file = $this->ipFile($ip);
        $now  = time();
        $hits = $this->readIpHits($file, $now);
        $hits[] = $now;
        $this->writeJson($file, $hits);

        $global = $this->globalFile();
        $count  = $this->readGlobalCount();
        $this->writeRaw($global, (string) ($count + 1));
    }

    /**
     * Read per-IP timestamps, pruning those older than 1 hour.
     * @return int[]
     */
    private function readIpHits(string $file, int $now): array
    {
        if (!is_file($file)) {
            return [];
        }
        $raw  = @file_get_contents($file);
        $data = $raw !== false ? json_decode($raw, true) : null;
        if (!is_array($data)) {
            return [];
        }
        $cutoff = $now - 3600;
        return array_values(array_filter($data, fn(mixed $t) => is_int($t) && $t > $cutoff));
    }

    private function readGlobalCount(): int
    {
        $file = $this->globalFile();
        if (!is_file($file)) {
            return 0;
        }
        $raw = @file_get_contents($file);
        return is_string($raw) ? (int) trim($raw) : 0;
    }

    private function ipFile(string $ip): string
    {
        return $this->dir . '/' . sha1($ip) . '.json';
    }

    private function globalFile(): string
    {
        return $this->dir . '/global-' . gmdate('Ymd') . '.count';
    }

    private function writeJson(string $file, array $data): void
    {
        $tmp = $file . '.tmp.' . getmypid();
        file_put_contents($tmp, json_encode(array_values($data)), LOCK_EX);
        rename($tmp, $file);
    }

    private function writeRaw(string $file, string $content): void
    {
        $tmp = $file . '.tmp.' . getmypid();
        file_put_contents($tmp, $content, LOCK_EX);
        rename($tmp, $file);
    }

    /**
     * Opportunistic cleanup: unlink per-IP files older than 24 hours.
     * Called on every allow() so no cron is needed.
     */
    private function pruneStale(): void
    {
        $cutoff = time() - 86400;
        foreach (glob($this->dir . '/*.json') ?: [] as $f) {
            if (@filemtime($f) < $cutoff) {
                @unlink($f);
            }
        }
    }
}
