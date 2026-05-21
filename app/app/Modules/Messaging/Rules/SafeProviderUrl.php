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

        // IPv6 literal có thể bọc trong [ ] (vd https://[::1]) — gỡ để validate.
        $host = trim(strtolower($parts['host'] ?? ''), '[]');
        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            $fail('Địa chỉ provider không được trỏ host nội bộ.');

            return;
        }

        // Thu thập IP cần kiểm: host là IP literal ⇒ chính nó; là hostname ⇒ resolve cả
        // A (gethostbyname chỉ IPv4) lẫn AAAA (dns_get_record) để chặn bypass IPv6.
        $ips = [];
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips[] = $host;
        } else {
            $v4 = gethostbyname($host);
            if (filter_var($v4, FILTER_VALIDATE_IP)) {
                $ips[] = $v4;
            }
            foreach ((array) @dns_get_record($host, DNS_AAAA) as $rec) {
                if (! empty($rec['ipv6'])) {
                    $ips[] = $rec['ipv6'];
                }
            }
        }

        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                $fail('Địa chỉ provider không được trỏ IP nội bộ/loopback.');

                return;
            }
        }
    }
}
