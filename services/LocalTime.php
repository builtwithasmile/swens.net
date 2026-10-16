<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Display-time helper: stored times are UTC (the DB session and PHP's
 * default zone are UTC), a person sees America/Vancouver. One zone constant
 * for this repo (fleet time audit 2026-10-16; precedent operator 3c45850).
 * Never use it for values that are stored or compared, only for output.
 */
final class LocalTime
{
    public const ZONE = 'America/Vancouver';

    /** A UTC 'Y-m-d H:i:s' DB value in the display zone; $empty when null/blank/zero/unparseable. */
    public static function show(?string $utc, string $format = 'Y-m-d H:i', string $empty = ''): string
    {
        if ($utc === null || $utc === '' || str_starts_with($utc, '0000-00-00')) {
            return $empty;
        }
        try {
            return (new \DateTimeImmutable($utc, new \DateTimeZone('UTC')))
                ->setTimezone(new \DateTimeZone(self::ZONE))->format($format);
        } catch (\Throwable $e) {
            return $empty;
        }
    }

}
