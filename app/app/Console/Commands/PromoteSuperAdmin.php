<?php

namespace CMBcoreSeller\Console\Commands;

use CMBcoreSeller\Models\User;
use Illuminate\Console\Command;

/**
 * SPEC 0020 — promote a user to super-admin (xuyên tenant). Idempotent.
 *
 *   $ php artisan admin:promote support@cmbcore.vn
 */
class PromoteSuperAdmin extends Command
{
    protected $signature = 'admin:promote {email : email của user cần nâng quyền super-admin}';

    protected $description = 'Promote a user to system super-admin (SPEC 0020)';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $user = User::query()->where('email', $email)->first();
        if (! $user) {
            $this->error("Không tìm thấy user với email [{$email}].");

            return self::FAILURE;
        }
        if ($user->is_super_admin) {
            $this->info("User {$email} đã là super-admin (idempotent — không đổi gì).");

            return self::SUCCESS;
        }
        $user->forceFill(['is_super_admin' => true])->save();
        $this->info("✔ User {$email} đã được nâng quyền super-admin.");

        return self::SUCCESS;
    }
}
