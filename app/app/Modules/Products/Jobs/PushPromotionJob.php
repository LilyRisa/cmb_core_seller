<?php

declare(strict_types=1);

namespace CMBcoreSeller\Modules\Products\Jobs;

use CMBcoreSeller\Integrations\Channels\DTO\PromotionItemDTO;
use CMBcoreSeller\Modules\Products\Models\ChannelPromotion;
use CMBcoreSeller\Modules\Products\Models\ChannelPromotionSku;
use CMBcoreSeller\Modules\Products\Services\PromotionService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Đẩy chiến dịch giảm giá lên sàn: tạo chương trình (nếu sàn có đối tượng) → đẩy SKU theo
 * BATCH (chunk theo `max_items_per_call` của sàn) → đánh dấu trạng thái từng SKU.
 *
 * Idempotent: `external_promotion_id` đã có ⇒ KHÔNG tạo lại (chỉ đẩy item). Lỗi 1 batch
 * không chặn batch khác. Queue `listings` (đã có trong Horizon — không thêm supervisor).
 */
class PushPromotionJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(public int $promotionId)
    {
        $this->onQueue('listings');
    }

    public function uniqueId(): string
    {
        return "promo-push:{$this->promotionId}";
    }

    public function handle(PromotionService $svc, CurrentTenant $tenant): void
    {
        // The queue worker runs without a request-bound tenant, so the global TenantScope
        // would constrain every query (including the `skus` eager load and ChannelAccount
        // lookup in authFor()) to tenant_id=0 and silently see nothing. Load the promotion
        // past the scope to discover its tenant, then run the whole push AS that tenant so
        // every tenant-scoped read below resolves correctly. Mirrors PushListingJob.
        $promo = ChannelPromotion::withoutGlobalScope(TenantScope::class)->find($this->promotionId);
        if ($promo === null) {
            return;
        }
        $shop = Tenant::query()->find($promo->tenant_id);
        if ($shop === null) {
            return;
        }

        $tenant->runAs($shop, fn () => $this->push($svc, $promo));
    }

    private function push(PromotionService $svc, ChannelPromotion $promo): void
    {
        $promo->loadMissing('skus');
        if ($promo->skus->isEmpty()) {
            return;
        }

        $conn = $svc->connector($promo->provider);
        $auth = $svc->authFor($promo);
        $caps = $conn->promotionCapabilities();
        $campaign = $svc->toDraftDTO($promo);

        $promo->forceFill(['status' => ChannelPromotion::STATUS_PUSHING, 'last_error' => null])->save();

        try {
            // Tạo chương trình (sàn có đối tượng) — idempotent.
            if ($caps['has_program_object'] && ($promo->external_promotion_id === null || $promo->external_promotion_id === '')) {
                $result = $conn->createPromotion($auth, $campaign);
                if ($result->externalPromotionId !== null && $result->externalPromotionId !== '') {
                    $promo->forceFill(['external_promotion_id' => $result->externalPromotionId])->save();
                }
            }

            $max = max(1, (int) $caps['max_items_per_call']);
            $okAny = false;
            $failAny = false;

            foreach ($promo->skus->chunk($max) as $chunk) {
                $items = $chunk->map(fn (ChannelPromotionSku $s) => new PromotionItemDTO(
                    externalProductId: (string) $s->external_product_id,
                    externalSkuId: (string) $s->external_sku_id,
                    sellerSku: (string) $s->seller_sku,
                    basePrice: (int) $s->base_price,
                    discountType: $promo->discount_type,
                    discountValue: (int) $s->discount_value,
                    salePrice: (int) $s->sale_price,
                ))->values()->all();

                try {
                    $conn->putPromotionItems($auth, $promo->external_promotion_id, $campaign, $items);
                    $chunk->each(fn (ChannelPromotionSku $s) => $s->forceFill(['push_status' => ChannelPromotionSku::PUSH_OK, 'error' => null])->save());
                    $okAny = true;
                } catch (\Throwable $e) {
                    $msg = substr($e->getMessage(), 0, 240);
                    $chunk->each(fn (ChannelPromotionSku $s) => $s->forceFill(['push_status' => ChannelPromotionSku::PUSH_FAILED, 'error' => $msg])->save());
                    $failAny = true;
                }
            }

            $promo->forceFill([
                'status' => $okAny ? ChannelPromotion::STATUS_LIVE : ChannelPromotion::STATUS_FAILED,
                'pushed_at' => now(),
                'last_error' => $failAny ? ['message' => 'Một số SKU đẩy thất bại — xem chi tiết từng dòng.'] : null,
            ])->save();
        } catch (\Throwable $e) {
            $promo->forceFill([
                'status' => ChannelPromotion::STATUS_FAILED,
                'last_error' => ['message' => substr($e->getMessage(), 0, 240)],
            ])->save();
        }
    }
}
