<?php

namespace CMBcoreSeller\Console\Commands;

use CMBcoreSeller\Models\AdminUser;
use Illuminate\Console\Command;

/**
 * Spec 2026-05-17 — đặt lại mật khẩu admin.
 *
 *   php artisan admin:reset-password ops_lead
 *
 * Password prompt ẩn nếu không truyền `--password`. Min length 8 ký tự.
 */
class AdminResetPassword extends Command
{
    protected $signature = 'admin:reset-password {username} {--password=}';

    protected $description = 'Đặt lại mật khẩu admin (Spec 2026-05-17).';

    public function handle(): int
    {
        $username = (string) $this->argument('username');
        $admin = AdminUser::query()->where('username', $username)->first();
        if (! $admin) {
            $this->error("Không tìm thấy admin với username [{$username}].");

            return self::FAILURE;
        }

        $pw = $this->option('password') ?: $this->secret('Mật khẩu mới (≥ 8)');
        if (strlen((string) $pw) < 8) {
            $this->error('Password phải ≥ 8 ký tự.');

            return self::FAILURE;
        }

        $admin->forceFill(['password' => $pw])->save();
        $this->info("✔ Reset mật khẩu cho {$username}.");

        return self::SUCCESS;
    }
}
