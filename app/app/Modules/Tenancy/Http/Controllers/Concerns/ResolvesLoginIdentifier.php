<?php

namespace CMBcoreSeller\Modules\Tenancy\Http\Controllers\Concerns;

use CMBcoreSeller\Models\User;

/**
 * Resolve a login identifier to a user. Accepts a real email OR a sub-account
 * username "{name}@{5-char shop code}" (SPEC 0031). Usernames and emails live in
 * separate unique columns and never overlap, so a plain username→email fallback
 * is unambiguous.
 */
trait ResolvesLoginIdentifier
{
    protected function resolveLoginUser(string $login): ?User
    {
        // Username sinh tự động luôn lowercase (uniqueUsername), email lưu lowercase (User::email
        // mutator) — chuẩn hoá input về lowercase để đăng nhập không phân biệt hoa/thường.
        $login = mb_strtolower(trim($login));
        if ($login === '') {
            return null;
        }

        return User::query()->where('username', $login)->first()
            ?? User::query()->where('email', $login)->first();
    }
}
