<?php

namespace CMBcoreSeller\Console\Commands;

use CMBcoreSeller\Models\User;
use Illuminate\Console\Command;

/**
 * SPEC 0020 — demote a super-admin to regular user. Idempotent.
 *
 *   $ php artisan admin:demote support@cmbcore.vn
 */
class DemoteSuperAdmin extends Command
{
    protected $signature = 'admin:demote {email : email của user cần hạ quyền}';

    protected $description = 'Demote a super-admin to a regular user (SPEC 0020)';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $user = User::query()->where('email', $email)->first();
        if (! $user) {
            $this->error("Không tìm thấy user với email [{$email}].");

            return self::FAILURE;
        }
        if (! $user->is_super_admin) {
            $this->info("User {$email} không phải super-admin (idempotent — không đổi gì).");

            return self::SUCCESS;
        }
        $user->forceFill(['is_super_admin' => false])->save();
        $this->info("✔ User {$email} đã bị hạ quyền super-admin.");

        return self::SUCCESS;
    }
}
