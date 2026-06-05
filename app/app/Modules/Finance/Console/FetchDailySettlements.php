<?php

namespace CMBcoreSeller\Modules\Finance\Console;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Finance\Jobs\FetchSettlementsForShop;
use CMBcoreSeller\Modules\Finance\Services\SettlementService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * SPEC 0016 — kéo đối soát HẰNG NGÀY cho mọi gian hàng của các sàn đã bật finance.
 *
 * Lên lịch ở routes/console.php (dailyAt 02:00). Mỗi lần kéo lại N ngày gần nhất
 * (mặc định 7) để cập nhật trạng thái thanh toán statement đến muộn (PROCESSING→PAID).
 * Upsert ở {@see SettlementService} idempotent
 * theo (channel_account_id, external_id) ở header và (settlement_id, fee_type,
 * external_order_id, external_line_id, amount) ở line ⇒ overlap KHÔNG tạo dữ liệu trùng.
 *
 * Mỗi gian hàng → 1 job {@see FetchSettlementsForShop} đẩy lên Horizon queue `finance`.
 * Sàn chưa bật finance (cờ INTEGRATIONS_<SAN>_FINANCE) bị bỏ qua ngay (không dispatch).
 */
class FetchDailySettlements extends Command
{
    protected $signature = 'settlements:fetch-daily
        {--days=7 : Số ngày gần nhất kéo lại để cập nhật trạng thái}
        {--provider= : Chỉ kéo 1 sàn (tiktok|lazada|shopee)}';

    protected $description = 'Xếp lịch kéo đối soát cho mọi gian hàng của các sàn đã bật finance (queue finance).';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $from = Carbon::now()->subDays($days)->toDateString();
        $to = Carbon::now()->toDateString();

        $only = $this->option('provider');
        $providers = collect(['tiktok', 'lazada', 'shopee'])
            ->when($only, fn ($c) => $c->filter(fn ($p) => $p === $only))
            ->filter(fn ($p) => (bool) config("integrations.{$p}.finance_enabled", false))
            ->values()->all();

        if ($providers === []) {
            $this->info('Không sàn nào bật đối soát (INTEGRATIONS_*_FINANCE) — bỏ qua.');

            return self::SUCCESS;
        }

        $count = 0;
        ChannelAccount::query()->withoutGlobalScope(TenantScope::class)
            ->where('status', 'active')
            ->whereIn('provider', $providers)
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($from, $to, &$count) {
                foreach ($rows as $row) {
                    FetchSettlementsForShop::dispatch((int) $row->id, $from, $to);
                    $count++;
                }
            });

        $this->info("Đã xếp {$count} job kéo đối soát (".implode(', ', $providers).") khoảng {$from} → {$to}.");
        Log::info('finance.settlement.daily_dispatch', [
            'shops' => $count, 'providers' => $providers, 'from' => $from, 'to' => $to, 'days' => $days,
        ]);

        return self::SUCCESS;
    }
}
