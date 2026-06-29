<?php

namespace CMBcoreSeller\Modules\Channels\Jobs;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Services\OrderUpsertService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus as S;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Làm mới trạng thái đơn SÀN đang "treo" (refetch từ sàn) để không kẹt không-thao-tác-được vĩnh viễn.
 * Chạy per active channel account, mỗi ~2h. Tách biệt SyncOrdersForShop. Dùng force=true để bỏ qua
 * stale-guard source_updated_at (vd Lazada timestamp lệch cũ) — vẫn giữ tracking_stopped + sticky-forward.
 */
class RefreshStuckOrders implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $channelAccountId) {}

    public function uniqueId(): string
    {
        return 'refresh-stuck:'.$this->channelAccountId;
    }

    public function handle(ChannelRegistry $registry, OrderUpsertService $upsert, CurrentTenant $currentTenant): void
    {
        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->find($this->channelAccountId);
        if (! $account || $account->status !== ChannelAccount::STATUS_ACTIVE || ! $registry->has((string) $account->provider)) {
            return;
        }
        $tenant = Tenant::query()->find($account->tenant_id);
        if (! $tenant) {
            return;
        }
        $cfg = (array) config('integrations.order_refresh', []);
        $stuckHours = (int) ($cfg['stuck_hours'] ?? 2);
        $maxAgeDays = (int) ($cfg['max_age_days'] ?? 30);
        $batch = (int) ($cfg['batch'] ?? 200);
        $sleepMs = (int) ($cfg['sleep_ms'] ?? 300);

        $connector = $registry->for((string) $account->provider);

        $orders = Order::withoutGlobalScope(TenantScope::class)
            ->where('channel_account_id', $this->channelAccountId)
            ->whereIn('status', [S::Pending->value, S::Processing->value, S::ReadyToShip->value])
            ->where(function ($q) {
                $q->where('has_issue', true)
                    ->orWhereHas('shipments', function ($s) {
                        $s->whereIn('status', Shipment::OPEN_STATUSES)
                            ->whereNull('label_path')
                            ->where(fn ($w) => $w->whereNull('label_fetch_next_retry_at')->orWhere('label_fetch_next_retry_at', '<=', now()));
                    });
            })
            ->where(fn ($q) => $q->whereNull('last_synced_at')->orWhere('last_synced_at', '<', now()->subHours($stuckHours)))
            ->where('placed_at', '>=', now()->subDays($maxAgeDays))
            ->orderBy('id')
            ->limit($batch)
            ->get();

        foreach ($orders as $order) {
            try {
                $currentTenant->runAs($tenant, function () use ($connector, $account, $order, $upsert) {
                    $dto = $connector->fetchOrderDetail($account->authContext(), (string) $order->external_order_id);
                    $status = $connector->mapStatus($dto->rawStatus, $dto->raw);
                    $upsert->upsertWithStatus($dto, (int) $account->tenant_id, (int) $account->getKey(), 'refresh', $status, true);
                    $this->clearStaleIssue($order->getKey());
                });
            } catch (Throwable $e) {
                Log::warning('order.refresh_stuck_failed', ['order' => $order->getKey(), 'provider' => $account->provider, 'error' => $e->getMessage()]);
            }
            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        }
    }

    /**
     * Clear has_issue/issue_reason CŨ loại tem/tracking khi đơn đã tiến lên (đã shipped+/terminal-completed
     * HOẶC đã có tem). KHÔNG động 'SKU chưa ghép' / âm tồn.
     */
    private function clearStaleIssue(int $orderId): void
    {
        $o = Order::withoutGlobalScope(TenantScope::class)->with('shipments')->find($orderId);
        if (! $o || ! $o->has_issue) {
            return;
        }
        $advanced = in_array($o->status, [S::Shipped, S::Delivered, S::Completed, S::Returning, S::ReturnedRefunded, S::Cancelled], true)
            || $o->shipments->first(fn ($s) => filled($s->label_path)) !== null;
        if (! $advanced) {
            return;
        }
        $reason = (string) $o->issue_reason;
        $labelKeywords = ['phiếu giao hàng', 'mã vận đơn', 'sắp xếp vận chuyển', 'in đơn', 'Advance Fulfilment', 'COD đang chờ', 'chờ Shopee'];
        $isLabelIssue = false;
        foreach ($labelKeywords as $kw) {
            if (mb_stripos($reason, $kw) !== false) {
                $isLabelIssue = true;
                break;
            }
        }
        if ($isLabelIssue) {
            $o->forceFill(['has_issue' => false, 'issue_reason' => null])->save();
        }
    }
}
