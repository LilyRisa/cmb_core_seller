<?php

declare(strict_types=1);

namespace CMBcoreSeller\Modules\Products\Services;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\Contracts\PromotionConnector;
use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\DTO\PromotionDraftDTO;
use CMBcoreSeller\Integrations\Channels\DTO\PromotionItemDTO;
use CMBcoreSeller\Integrations\Channels\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Integrations\Channels\PublisherRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Products\Models\ChannelPromotion;
use CMBcoreSeller\Modules\Products\Models\ChannelPromotionSku;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Vòng đời chiến dịch giảm giá (tách biệt, KHÔNG đụng luồng listing/publish):
 * tạo/sửa nháp → chọn SKU (tính sale_price) → validate (chống chồng lấn SKU) → đẩy
 * (job) → đồng bộ từ sàn. Khác biệt sàn nằm ở {@see PromotionConnector}.
 */
final class PromotionService
{
    public function __construct(private PublisherRegistry $publishers) {}

    /** Connector hỗ trợ giảm giá của provider; chưa hỗ trợ ⇒ UnsupportedOperation. */
    public function connector(string $provider): PromotionConnector
    {
        $conn = $this->publishers->for($provider);
        if (! $conn instanceof PromotionConnector) {
            throw UnsupportedOperation::for($provider, 'promotions.manage');
        }

        return $conn;
    }

    /** @return array{max_items_per_call:int, supports_percent:bool, has_program_object:bool, supports_time_of_day:bool} */
    public function capabilities(string $provider): array
    {
        return $this->connector($provider)->promotionCapabilities();
    }

    /** Giá sale tuyệt đối từ kiểu giảm. */
    public static function computeSalePrice(int $basePrice, string $discountType, int $discountValue): int
    {
        if ($discountType === ChannelPromotion::DISCOUNT_PERCENT) {
            $v = max(0, min(99, $discountValue));

            return (int) round($basePrice * (100 - $v) / 100);
        }

        return max(0, $discountValue);
    }

    /** @param  array<string,mixed>  $data */
    public function createDraft(int $channelAccountId, array $data, ?int $userId = null): ChannelPromotion
    {
        $account = ChannelAccount::query()->findOrFail($channelAccountId);
        $this->connector($account->provider); // chặn sớm nếu sàn không hỗ trợ

        return ChannelPromotion::query()->create([
            'tenant_id' => (int) $account->tenant_id,
            'channel_account_id' => $channelAccountId,
            'provider' => $account->provider,
            'title' => (string) $data['title'],
            'discount_type' => $data['discount_type'] ?? ChannelPromotion::DISCOUNT_FIXED,
            'starts_at' => CarbonImmutable::parse((string) $data['starts_at']),
            'ends_at' => CarbonImmutable::parse((string) $data['ends_at']),
            'status' => ChannelPromotion::STATUS_DRAFT,
            'source' => 'app',
            'created_by' => $userId,
        ]);
    }

    /** @param  array<string,mixed>  $data */
    public function updateDraft(ChannelPromotion $promo, array $data): ChannelPromotion
    {
        abort_unless($promo->status === ChannelPromotion::STATUS_DRAFT, 422, 'Chỉ sửa được chiến dịch ở trạng thái nháp.');
        foreach (['title', 'discount_type'] as $k) {
            if (array_key_exists($k, $data)) {
                $promo->{$k} = $data[$k];
            }
        }
        foreach (['starts_at', 'ends_at'] as $k) {
            if (array_key_exists($k, $data)) {
                $promo->{$k} = CarbonImmutable::parse((string) $data[$k]);
            }
        }
        $promo->save();

        // Đổi kiểu giảm ⇒ tính lại sale_price các SKU đã chọn.
        if (array_key_exists('discount_type', $data)) {
            foreach ($promo->skus()->get() as $sku) {
                $sku->discount_type = $promo->discount_type;
                $sku->sale_price = self::computeSalePrice((int) $sku->base_price, $promo->discount_type, (int) $sku->discount_value);
                $sku->save();
            }
        }

        return $promo;
    }

    /**
     * Đặt danh sách SKU + mức giảm cho chiến dịch (thay thế toàn bộ). Tính sale_price.
     *
     * @param  list<array<string,mixed>>  $rows
     */
    public function setSkus(ChannelPromotion $promo, array $rows): ChannelPromotion
    {
        abort_unless($promo->status === ChannelPromotion::STATUS_DRAFT, 422, 'Chỉ sửa SKU khi chiến dịch còn nháp.');

        DB::transaction(function () use ($promo, $rows) {
            $promo->skus()->delete();
            foreach ($rows as $r) {
                $base = (int) ($r['base_price'] ?? 0);
                $value = (int) ($r['discount_value'] ?? 0);
                ChannelPromotionSku::query()->create([
                    'tenant_id' => (int) $promo->tenant_id,
                    'promotion_id' => (int) $promo->getKey(),
                    'channel_listing_id' => $r['channel_listing_id'] ?? null,
                    'external_product_id' => $r['external_product_id'] ?? null,
                    'external_sku_id' => $r['external_sku_id'] ?? null,
                    'seller_sku' => $r['seller_sku'] ?? null,
                    'base_price' => $base,
                    'discount_type' => $promo->discount_type,
                    'discount_value' => $value,
                    'sale_price' => self::computeSalePrice($base, $promo->discount_type, $value),
                    'push_status' => ChannelPromotionSku::PUSH_PENDING,
                ]);
            }
        });

        return $promo->fresh(['skus']) ?? $promo;
    }

    /**
     * external_sku_id đang BẬN (thuộc chiến dịch draft/pushing/live khác) trong cùng gian hàng
     * — để FE tô xám không cho chọn + chặn chồng lấn khi đẩy. Hợp nhất DB + (tuỳ chọn) sàn.
     *
     * @return list<string>
     */
    public function busySkuIds(int $channelAccountId, ?int $exceptPromotionId = null): array
    {
        return array_keys($this->busyPromoPrices($channelAccountId, $exceptPromotionId));
    }

    /**
     * Map khoá-BẬN → giá giảm (VND) cho các SKU/sản phẩm đang trong chương trình. KHOÁ = external_sku_id nếu
     * có, NGƯỢC lại external_product_id (item Shopee KHÔNG biến thể: giảm giá nằm ở item_id, model rỗng — trước
     * đây bị bỏ sót nên đơn hiện ra hết). Gộp: (1) chiến dịch app đang chiếm + (2) chương trình ĐANG/SẮP chạy
     * trên sàn (giá thật ưu tiên). FE tô xám + hiện giá theo khoá này.
     *
     * @return array<string,int>
     */
    public function busyPromoPrices(int $channelAccountId, ?int $exceptPromotionId = null): array
    {
        // 1) DB: SKU thuộc chiến dịch app đang chiếm (draft/pushing/live).
        $map = [];
        $db = ChannelPromotionSku::query()
            ->whereHas('promotion', function ($p) use ($channelAccountId, $exceptPromotionId) {
                $p->where('channel_account_id', $channelAccountId)
                    ->whereIn('status', [ChannelPromotion::STATUS_DRAFT, ChannelPromotion::STATUS_PUSHING, ChannelPromotion::STATUS_LIVE]);
                if ($exceptPromotionId !== null) {
                    $p->where('id', '!=', $exceptPromotionId);
                }
            })
            ->whereNotNull('external_sku_id')
            ->get(['external_sku_id', 'sale_price']);
        foreach ($db as $row) {
            $map[(string) $row->external_sku_id] = (int) $row->sale_price;
        }

        // 2) SÀN: chương trình ngoài app (giá thật đang chạy — ưu tiên ghi đè).
        foreach ($this->channelBusyPromos($channelAccountId) as $key => $price) {
            $map[$key] = $price;
        }

        return $map;
    }

    /**
     * Khoá-bận → giá giảm cho chương trình ĐANG/SẮP chạy TRÊN SÀN (best-effort, cache 60s). Khoá = sku_id ||
     * product_id. Sàn không liệt kê được chương trình (Lazada) ⇒ []. Lỗi token/API ⇒ [] (không chặn UI).
     *
     * @return array<string,int>
     */
    private function channelBusyPromos(int $channelAccountId): array
    {
        return Cache::remember("promo_busy_san:$channelAccountId", now()->addSeconds(60), function () use ($channelAccountId) {
            try {
                $account = ChannelAccount::query()->find($channelAccountId);
                if (! $account) {
                    return [];
                }
                $map = [];
                foreach ($this->connector($account->provider)->listPromotions($account->authContext()) as $p) {
                    if ($p->status === 'ended') {
                        continue;
                    }
                    foreach ($p->items as $it) {
                        $key = ! empty($it['external_sku_id']) ? (string) $it['external_sku_id'] : (string) ($it['external_product_id'] ?? '');
                        if ($key !== '') {
                            $map[$key] = (int) ($it['sale_price'] ?? 0);
                        }
                    }
                }

                return $map;
            } catch (\Throwable) {
                return [];
            }
        });
    }

    /** Kiểm tra chồng lấn trước khi đẩy; trả danh sách external_sku_id bị trùng (rỗng = OK). @return list<string> */
    public function conflictingSkuIds(ChannelPromotion $promo): array
    {
        $busy = $this->busySkuIds((int) $promo->channel_account_id, (int) $promo->getKey());
        $mine = $promo->skus()->whereNotNull('external_sku_id')->pluck('external_sku_id')->all();

        return array_values(array_intersect($mine, $busy));
    }

    public function toDraftDTO(ChannelPromotion $promo): PromotionDraftDTO
    {
        $items = $promo->skus->map(fn (ChannelPromotionSku $s) => new PromotionItemDTO(
            externalProductId: (string) $s->external_product_id,
            externalSkuId: (string) $s->external_sku_id,
            sellerSku: (string) $s->seller_sku,
            basePrice: (int) $s->base_price,
            discountType: $promo->discount_type,
            discountValue: (int) $s->discount_value,
            salePrice: (int) $s->sale_price,
        ))->all();

        return new PromotionDraftDTO(
            title: $promo->title,
            startAt: $promo->starts_at?->toImmutable() ?? CarbonImmutable::now(),
            endAt: $promo->ends_at?->toImmutable() ?? CarbonImmutable::now(),
            discountType: $promo->discount_type,
            items: $items,
        );
    }

    public function authFor(ChannelPromotion $promo): AuthContext
    {
        return ChannelAccount::query()->findOrFail($promo->channel_account_id)->authContext();
    }

    /** Kết thúc chiến dịch trên sàn (Shopee/TikTok: 1 call; Lazada: gỡ SalePrice theo batch). */
    public function endOnChannel(ChannelPromotion $promo): void
    {
        $conn = $this->connector($promo->provider);
        $auth = $this->authFor($promo);
        $caps = $conn->promotionCapabilities();
        $campaign = $this->toDraftDTO($promo->loadMissing('skus'));

        if ($caps['has_program_object']) {
            if ($promo->external_promotion_id !== null && $promo->external_promotion_id !== '') {
                $conn->endPromotion($auth, $promo->external_promotion_id, $campaign, []);
            }
        } else {
            $max = max(1, (int) $caps['max_items_per_call']);
            foreach (array_chunk($campaign->items, $max) as $batch) {
                $conn->endPromotion($auth, null, $campaign, $batch);
            }
        }

        $promo->forceFill(['status' => ChannelPromotion::STATUS_ENDED])->save();
    }

    /**
     * Đồng bộ chiến dịch ĐANG có trên sàn về DB (tab "đã đẩy"): upsert source=sync.
     * Sàn không có đối tượng chương trình (Lazada) ⇒ connector trả [] ⇒ no-op.
     *
     * @return int số chiến dịch đồng bộ
     */
    public function syncFromChannel(int $channelAccountId): int
    {
        $account = ChannelAccount::query()->findOrFail($channelAccountId);
        $conn = $this->connector($account->provider);
        $auth = $account->authContext();

        $count = 0;
        foreach ($conn->listPromotions($auth) as $p) {
            $status = match ($p->status) {
                'ended' => ChannelPromotion::STATUS_ENDED,
                default => ChannelPromotion::STATUS_LIVE, // upcoming/ongoing đều coi là đã có trên sàn
            };

            $promo = ChannelPromotion::withoutGlobalScope(TenantScope::class)->firstOrNew([
                'channel_account_id' => $channelAccountId,
                'external_promotion_id' => $p->externalPromotionId,
            ]);
            if (! $promo->exists) {
                $promo->tenant_id = (int) $account->tenant_id;
                $promo->provider = $account->provider;
                $promo->source = 'sync';
            }
            $promo->title = $p->title;
            $promo->status = $status;
            $promo->starts_at = $p->startAt ? CarbonImmutable::parse($p->startAt->toIso8601String()) : $promo->starts_at;
            $promo->ends_at = $p->endAt ? CarbonImmutable::parse($p->endAt->toIso8601String()) : $promo->ends_at;
            $promo->synced_at = now();
            $promo->save();

            // Cập nhật SKU của chiến dịch đồng bộ (để biết SKU đang bận + hiển thị).
            $promo->skus()->delete();
            foreach ($p->items as $it) {
                ChannelPromotionSku::query()->create([
                    'tenant_id' => (int) $account->tenant_id,
                    'promotion_id' => (int) $promo->getKey(),
                    'external_product_id' => $it['external_product_id'],
                    'external_sku_id' => $it['external_sku_id'],
                    'sale_price' => (int) $it['sale_price'],
                    'push_status' => ChannelPromotionSku::PUSH_OK,
                ]);
            }
            $count++;
        }

        return $count;
    }
}
