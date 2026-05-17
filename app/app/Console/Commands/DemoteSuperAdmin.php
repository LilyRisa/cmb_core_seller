<?php

namespace CMBcoreSeller\Console\Commands;

use CMBcoreSeller\Models\AdminUser;
use Illuminate\Console\Command;

/**
 * Spec 2026-05-17 — vô hiệu hoá admin (is_active=false).
 *
 *   php artisan admin:demote ops_lead
 *
 * Không xoá row — toggle cờ. Khôi phục bằng `admin:create` mới (hoặc trực tiếp
 * trên DB). Idempotent.
 */
class DemoteSuperAdmin extends Command
{
    protected $signature = 'admin:demote {username : username admin cần vô hiệu hoá}';

    protected $description = 'Vô hiệu hoá tài khoản super-admin (set is_active=false).';

    public function handle(): int
    {
        $username = (string) $this->argument('username');
        $admin = AdminUser::query()->where('username', $username)->first();
        if (! $admin) {
            $this->error("Không tìm thấy admin với username [{$username}].");

            return self::FAILURE;
        }
        if (! $admin->is_active) {
            $this->info("Admin {$username} đã bị vô hiệu hoá (idempotent — không đổi gì).");

            return self::SUCCESS;
        }
        $admin->forceFill(['is_active' => false])->save();
        $this->info("✔ Vô hiệu hoá admin {$username}.");

        return self::SUCCESS;
    }
}
