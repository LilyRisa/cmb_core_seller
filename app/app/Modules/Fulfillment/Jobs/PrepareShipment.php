<?php

namespace CMBcoreSeller\Modules\Fulfillment\Jobs;

use CMBcoreSeller\Modules\Fulfillment\Services\ShipmentService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * "Chuẩn bị hàng" hàng loạt chạy NỀN (queue `fulfillment`): toàn bộ phần gọi sàn (arrange) + lấy tem/AWB +
 * flip status Processing — TRƯỚC đây chạy đồng bộ trong php-fpm gây 504/orphan khi bulk nhiều đơn (SPEC
 * 2026-06-26). CHỈ dùng cho bulk; single `/orders/{id}/ship` vẫn đồng bộ (app mobile/quét kho phụ thuộc).
 *
 * Idempotent 3 lớp: ShouldBeUnique(orderId) dedup lúc enqueue + WithoutOverlapping(orderId) chống chạy
 * song song cùng đơn + check vận đơn open() trong `createForOrder`. Job chạy trong worker KHÔNG có
 * request-bound tenant ⇒ `runAs($shop)` để query tenant-scoped (ChannelAccount, carrier account...) resolve
 * đúng (TenantScope ép tenant_id ?? 0 nếu thiếu — TenantScope.php).
 */
class PrepareShipment implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** Dedup các job trùng orderId đang chờ trong queue (giây). */
    public int $uniqueFor = 60;

    /**
     * @param  array<string, mixed>  $opts
     */
    public function __construct(
        public readonly int $orderId,
        public readonly ?int $carrierAccountId = null,
        public readonly ?string $service = null,
        public readonly array $opts = [],
        public readonly ?int $userId = null,
    ) {}

    /** @return list<int> giây giữa các lượt retry */
    public function backoff(): array
    {
        return [10, 30, 60, 120, 300];
    }

    public function uniqueId(): string
    {
        return (string) $this->orderId;
    }

    /** @return list<object> */
    public function middleware(): array
    {
        // Chống 2 job cùng đơn chạy song song (race tạo trùng vận đơn). dontRelease: job trùng bị bỏ, không
        // re-queue (ShouldBeUnique + open() check đã đảm bảo không sót).
        return [(new WithoutOverlapping((string) $this->orderId))->expireAfter(180)->dontRelease()];
    }

    public function handle(ShipmentService $service, CurrentTenant $tenant): void
    {
        $order = Order::withoutGlobalScope(TenantScope::class)->find($this->orderId);
        if (! $order) {
            return;
        }
        $shop = Tenant::query()->find($order->tenant_id);
        if (! $shop) {
            return;
        }
        // Chạy như tenant của đơn để TenantScope (ChannelAccount, carrier account...) resolve đúng.
        $tenant->runAs($shop, function () use ($service, $order) {
            $service->createForOrder(
                $order,
                $this->carrierAccountId,
                $this->service,
                $this->opts,
                $this->userId,
            );
        });
    }

    /**
     * Hết tries (lỗi sàn kéo dài) ⇒ gắn cờ has_issue để FE hiện + cho user "Nhận phiếu giao hàng".
     */
    public function failed(\Throwable $e): void
    {
        $order = Order::withoutGlobalScope(TenantScope::class)->find($this->orderId);
        if ($order) {
            $order->forceFill([
                'has_issue' => true,
                'issue_reason' => Str::limit('Chuẩn bị hàng thất bại: '.$e->getMessage(), 240),
            ])->save();
        }
        Log::warning('shipment.prepare_async_failed', ['order' => $this->orderId, 'error' => $e->getMessage()]);
    }
}
