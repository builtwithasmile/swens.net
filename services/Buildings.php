<?php
declare(strict_types=1);

namespace App\Services;

/**
 * The live public destinations the header/admin still reference. The six-place
 * "buildings" metaphor (the Compound map) was retired with the Porch; only the
 * two real Porch destinations remain — /office and /gate. Kept as a small
 * registry because the interior header reads ['name'] and the admin post-form
 * reads postable()/postableAll() for its building dropdown.
 */
final class Buildings
{
    /** slug => [name, accent (CSS token name), open (bool), plaque, status] */
    public const ALL = [
        'office' => [
            'name'   => 'The Office',
            'accent' => 'gold',
            'open'   => true,
            'plaque' => 'The companies that pay for all this.',
            'status' => 'Open', // TODO-lyra
        ],
        'gate' => [
            'name'   => 'The Gate',
            'accent' => 'gold',
            'open'   => true,
            'plaque' => 'Who\'s Swens, and how to ask for a key.', // VOICE: Swens signage
            'status' => 'Open', // TODO-lyra
        ],
    ];

    /**
     * Non-public keyed buckets. Deliberately NOT in self::ALL, so they never
     * appear on the public map (the map reads ALL) and are not addressable via
     * the public building/permalink routes. Keyed posts live here; /inside
     * surfaces them by tier='keyed'. slug => admin-dropdown label.
     */
    public const KEYED_SECTIONS = ['inside' => 'inside (keyed)'];

    /**
     * Buildings that can hold posts (all except gate).
     * Used by S2 route whitelist and admin dropdown.
     * @return string[]
     */
    public static function postable(): array
    {
        return array_values(array_diff(array_keys(self::ALL), ['gate']));
    }

    /**
     * Every value the admin post-form may set as `building` — public buildings
     * plus the keyed buckets. Public routes never reference the keyed buckets,
     * so a keyed post can never be reached through a public page.
     * @return string[]
     */
    public static function postableAll(): array
    {
        return array_merge(self::postable(), array_keys(self::KEYED_SECTIONS));
    }
}
