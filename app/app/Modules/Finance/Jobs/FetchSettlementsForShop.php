<?php

namespace CMBcoreSeller\Modules\Finance\Jobs;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Finance\Services\SettlementService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job kéo đối soát một gian hàng — gọi `Connector::fetchSettlements` qua {@see SettlementService}. SPEC 0016.
 *
 * Idempotent: chạy lại với cùng khoảng `from/to` ⇒ upsert (không tạo trùng). Lịch chạy: tenant tự dispatch ở UI
 * "Đối soát sàn" — hoặc scheduled (Phase 6.x): mỗi ngày kéo 7 ngày gần nhất.
 */
class FetchSettlementsForShop implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public readonly int $channelAccountId, public readonly ?string $from = null, public readonly ?string $to = null) {}

    public function handle(SettlementService $service): void
    {
        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->find($this->channelAccountId);
        if (! $account) {
            return;
        }
        $from = $this->from ? CarbonImmutable::parse($this->from) : null;
        $to = $this->to ? CarbonImmutable::parse($this->to) : null;
        try {
            $r = $service->fetchForShop($account, $from, $to);
            Log::info('finance.settlement.fetched', ['shop' => $account->getKey(), 'fetched' => $r['fetched'], 'lines' => $r['lines']]);
        } catch (\Throwable $e) {
            Log::warning('finance.settlement.fetch_failed', ['shop' => $account->getKey(), 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
