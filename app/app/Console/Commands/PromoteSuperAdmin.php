<?php

namespace CMBcoreSeller\Console\Commands;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Spec 2026-05-17 — promote user-by-email lên super-admin.
 *
 * Tạo row `admin_users` mirror từ user thường (giữ tên + email + password hash
 * cũ). Username sinh từ local-part của email (sanitize); collision → suffix.
 * Idempotent: admin với email tồn tại ⇒ exit 0 không đổi gì. Sau promote, gọi
 * `admin:reset-password <username>` nếu muốn đặt password riêng.
 */
class PromoteSuperAdmin extends Command
{
    protected $signature = 'admin:promote {email : email của user cần nâng quyền super-admin}';

    protected $description = 'Promote user-by-email lên super-admin (tạo admin_users mirror).';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $user = User::query()->where('email', $email)->first();
        if (! $user) {
            $this->error("Không tìm thấy user với email [{$email}].");

            return self::FAILURE;
        }
        if (AdminUser::query()->where('email', $email)->exists()) {
            $this->info("Admin với email {$email} đã tồn tại (idempotent — không đổi gì).");

            return self::SUCCESS;
        }

        $base = $this->sanitize(Str::before($email, '@'));
        if ($base === '') {
            $base = "admin_{$user->id}";
        }
        $username = $base;
        $i = 1;
        while (AdminUser::query()->where('username', $username)->exists()) {
            $username = $base.'_'.$i++;
        }

        AdminUser::create([
            'username' => $username,
            'email' => $email,
            'name' => $user->name,
            'password' => $user->password,
            'is_active' => true,
        ]);

        $this->info("✔ Promote {$email} → admin (username={$username}). Hãy chạy `admin:reset-password {$username}` để đặt mật khẩu mới.");

        return self::SUCCESS;
    }

    private function sanitize(string $raw): string
    {
        $s = strtolower($raw);
        $s = preg_replace('/[^a-z0-9._-]/', '', $s) ?? '';
        $s = trim($s, '._-');

        return strlen($s) >= 3 ? substr($s, 0, 32) : '';
    }
}
