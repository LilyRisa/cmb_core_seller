<?php

namespace CMBcoreSeller\Modules\Customers\Support;

/**
 * Normalizes a raw buyer phone to a canonical form so the same person matches
 * across orders. VN numbers collapse to `0xxxxxxxxx` (10 digits); foreign numbers
 * keep `+<digits>` (8..15). Masked / invalid input → null (won't be matched).
 *
 * Algorithm + case table: docs/03-domain/customers-and-buyer-reputation.md §2,
 * SPEC 0002 §4.1.
 */
final class CustomerPhoneNormalizer
{
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        // Masked digits (TikTok masks the phone once the order is delivered / on data_deletion).
        if (preg_match('/[*xX]/u', $raw) === 1) {
            return null;
        }

        $hasPlus = str_starts_with($raw, '+');
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if ($digits === '') {
            return null;
        }

        // +84xxxxxxxxx / 84xxxxxxxxx (11 digits) → 0xxxxxxxxx
        if (str_starts_with($digits, '84') && strlen($digits) === 11) {
            return '0'.substr($digits, 2);
        }
        // 0xxxxxxxxx (10 digits, VN canonical) → keep
        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return $digits;
        }
        // 9 digits starting 84? unlikely; fall through to validation below.

        if ($hasPlus) {
            // International (non-VN): canonical "+<digits>", 8..15 digits.
            if (strlen($digits) >= 8 && strlen($digits) <= 15) {
                return '+'.$digits;
            }

            return null;
        }

        return null;
    }

    public static function hash(string $normalized): string
    {
        return hash('sha256', $normalized);
    }

    /** Convenience: normalize then hash, or null if not normalizable. */
    public static function normalizeAndHash(?string $raw): ?string
    {
        $n = self::normalize($raw);

        return $n === null ? null : self::hash($n);
    }
}
