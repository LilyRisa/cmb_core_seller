<?php

namespace CMBcoreSeller\Console\Commands;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Dọn tài khoản ĐĂNG KÝ RỒI KHÔNG XÁC MINH email (rác DB từ email giả/bot) — SPEC
 * 2026-06-10. Chỉ xóa khi: email_verified_at NULL, quá hạn (mặc định 1 ngày),
 * KHÔNG phải sub-account, và mọi tenant của user đều RỖNG (không có gian hàng / tài
 * khoản quảng cáo / đơn / SKU) và user là thành viên DUY NHẤT. Xóa transaction từng
 * account; lỗi (FK lạ…) ⇒ rollback + bỏ qua + log (an toàn, không để trạng thái dở).
 */
class PruneUnverifiedUsers extends Command
{
    protected $signature = 'users:prune-unverified {--days=1 : Số ngày sau đăng ký mà chưa xác minh} {--dry-run : Chỉ liệt kê, không xóa}';

    protected $description = 'Xóa tài khoản chưa xác minh email quá hạn và không có dữ liệu (chống rác DB).';

    /** Bảng cho biết tenant CÓ dữ liệu thật ⇒ KHÔNG xóa. */
    private const BUSINESS_TABLES = ['channel_accounts', 'ad_accounts', 'orders', 'skus'];

    /** Bảng phụ TỰ-SINH lúc đăng ký — dọn theo tenant_id trước khi xóa tenant (đều có guard). */
    private const CLEANUP_TABLES = [
        'subscriptions', 'tenant_user', 'ai_credit_wallets', 'billing_profiles',
        'messaging_settings', 'messaging_account_meta', 'notifications', 'audit_logs',
        'invoices', 'payments',
    ];

    public function handle(): int
    {
        $days = max(0, (int) $this->option('days'));
        $dry = (bool) $this->option('dry-run');
        $cutoff = Carbon::now()->subDays($days);

        $candidates = User::query()
            ->whereNull('email_verified_at')
            ->where('created_at', '<', $cutoff)
            ->where('is_sub_account', false)
            ->orderBy('id')
            ->get();

        $deleted = 0;
        $skipped = 0;
        foreach ($candidates as $user) {
            $tenantIds = DB::table('tenant_user')->where('user_id', $user->id)->pluck('tenant_id')->all();

            if (! $this->isPrunable($tenantIds)) {
                $skipped++;

                continue;
            }

            if ($dry) {
                $this->line("DRY: sẽ xóa user #{$user->id} {$user->email} + tenant(s) ".implode(',', $tenantIds));
                $deleted++;

                continue;
            }

            try {
                DB::transaction(function () use ($user, $tenantIds) {
                    foreach ($tenantIds as $tid) {
                        foreach (self::CLEANUP_TABLES as $table) {
                            if (Schema::hasTable($table) && Schema::hasColumn($table, 'tenant_id')) {
                                DB::table($table)->where('tenant_id', $tid)->delete();
                            }
                        }
                        // forceDelete: Tenant dùng SoftDeletes ⇒ xóa THẬT để dọn rác (cascade
                        // các bảng FK cascadeOnDelete như roles).
                        Tenant::withoutGlobalScopes()->whereKey($tid)->forceDelete();
                    }
                    DB::table('tenant_user')->where('user_id', $user->id)->delete();
                    User::withoutGlobalScopes()->whereKey($user->id)->delete();
                });
                $deleted++;
            } catch (\Throwable $e) {
                $skipped++;
                Log::warning('users.prune_unverified.skip', ['user' => $user->id, 'error' => $e->getMessage()]);
            }
        }

        $this->info(($dry ? '[DRY] ' : '')."Đã xử lý: xóa {$deleted}, bỏ qua {$skipped} (chưa xác minh > {$days} ngày).");

        return self::SUCCESS;
    }

    /** Mọi tenant đều RỖNG + user là thành viên duy nhất ⇒ prunable. Rỗng tenant list ⇒ prunable (user mồ côi). */
    private function isPrunable(array $tenantIds): bool
    {
        foreach ($tenantIds as $tid) {
            // Có thành viên khác ⇒ tenant dùng chung, không xóa.
            if (DB::table('tenant_user')->where('tenant_id', $tid)->count() > 1) {
                return false;
            }
            foreach (self::BUSINESS_TABLES as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'tenant_id')
                    && DB::table($table)->where('tenant_id', $tid)->exists()) {
                    return false;
                }
            }
        }

        return true;
    }
}
