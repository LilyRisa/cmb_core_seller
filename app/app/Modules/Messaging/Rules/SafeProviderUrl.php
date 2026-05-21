<?php

namespace CMBcoreSeller\Modules\Messaging\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * base_url provider phải HTTPS và KHÔNG trỏ host nội bộ (chống SSRF/MITM).
 * Null/empty ⇒ bỏ qua (dùng default endpoint của adapter).
 */
class SafeProviderUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $parts = parse_url((string) $value);
        if (($parts['scheme'] ?? '') !== 'https') {
            $fail('Địa chỉ provider phải dùng HTTPS.');

            return;
        }

        $host = strtolower($parts['host'] ?? '');
        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            $fail('Địa chỉ provider không được trỏ host nội bộ.');

            return;
        }

        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);
        if (filter_var($ip, FILTER_VALIDATE_IP) && ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            $fail('Địa chỉ provider không được trỏ IP nội bộ/loopback.');
        }
    }
}
