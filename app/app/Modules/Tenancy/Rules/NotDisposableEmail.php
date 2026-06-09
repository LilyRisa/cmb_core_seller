<?php

namespace CMBcoreSeller\Modules\Tenancy\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Chặn email dùng-một-lần (disposable) lúc đăng ký để tránh rác DB — SPEC 2026-06-10.
 * So domain (sau @, lowercase) với `config('captcha.disposable_domains')`.
 */
class NotDisposableEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $email = strtolower(trim((string) $value));
        $at = strrpos($email, '@');
        if ($at === false) {
            return; // 'email' rule khác lo định dạng
        }
        $domain = substr($email, $at + 1);

        $denylist = array_map('strtolower', (array) config('captcha.disposable_domains', []));
        if (in_array($domain, $denylist, true)) {
            $fail('Email dùng một lần không được chấp nhận. Vui lòng dùng email thật.');
        }
    }
}
