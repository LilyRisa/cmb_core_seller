<?php

namespace CMBcoreSeller\Modules\Orders\Http\Resources;

use CMBcoreSeller\Modules\Customers\Contracts\CustomerProfileContract;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 *
 * Conventions: snake_case fields; money as integer VND đồng + currency; times
 * ISO-8601 UTC; status = canonical code + status_label + raw_status.
 * See docs/05-api/conventions.md §5.
 */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // line-item count: prefer the withCount aggregate (list); else count loaded items.
        $itemsCount = $this->items_count !== null
            ? (int) $this->items_count
            : ($this->relationLoaded('items') ? $this->items->count() : null);
        // first item image for the list thumbnail (set by OrderController::index, or from loaded items)
        $thumbnail = $this->getAttribute('thumbnail')
            ?: ($this->relationLoaded('items') ? $this->items->first(fn (OrderItem $i) => filled($i->image))?->image : null);
        // estimated profit after platform fee (set by OrderController via OrderProfitService) — SPEC 0012
        $profit = $this->getAttribute('_profit');

        return [
            'customer' => $this->customerCard($request),
            'id' => $this->id,
            'source' => $this->source,
            'channel_account_id' => $this->channel_account_id,
            'thumbnail' => $thumbnail,
            'channel_account' => $this->whenLoaded('channelAccount', fn () => $this->channelAccount ? [
                'id' => $this->channelAccount->id, 'name' => $this->channelAccount->effectiveName(), 'provider' => $this->channelAccount->provider,
            ] : null),
            'carrier' => $this->carrier,
            'external_order_id' => $this->external_order_id,
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_pre_shipment' => $this->status->isPreShipment(),
            'is_terminal' => $this->status->isTerminal(),
            'raw_status' => $this->raw_status,
            'payment_status' => $this->payment_status,
            'buyer_name' => $this->buyer_name,
            'buyer_phone_masked' => $this->maskedBuyerPhone(),
            'shipping_address' => $this->shipping_address,
            'currency' => $this->currency,
            'item_total' => $this->item_total,
            'shipping_fee' => $this->shipping_fee,
            'platform_discount' => $this->platform_discount,
            'seller_discount' => $this->seller_discount,
            'tax' => $this->tax,
            'cod_amount' => $this->cod_amount,
            // SPEC 0021 — UI mới (taodon.png): "Tiền chuyển khoản" + "Phụ thu".
            'prepaid_amount' => (int) ($this->prepaid_amount ?? 0),
            'surcharge' => (int) ($this->surcharge ?? 0),
            'meta' => $this->meta ?? null,
            'grand_total' => $this->grand_total,
            'profit' => is_array($profit) ? $profit : null,   // {cogs, platform_fee, shipping_fee, estimated_profit, platform_fee_pct, cost_complete} — SPEC 0012
            'out_of_stock' => (bool) $this->getAttribute('out_of_stock'),   // đơn có ≥1 SKU âm tồn ⇒ chặn "Chuẩn bị hàng" — SPEC 0013
            'is_cod' => $this->is_cod,
            'fulfillment_type' => $this->fulfillment_type,
            'items_count' => $itemsCount,
            'has_issue' => $this->has_issue,
            'issue_reason' => $this->issue_reason,
            'tags' => $this->tags ?? [],
            'note' => $this->note,
            'packages' => $this->packages ?? [],
            'placed_at' => $this->placed_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'shipped_at' => $this->shipped_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancel_reason' => $this->cancel_reason,
            'source_updated_at' => $this->source_updated_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'status_history' => OrderStatusHistoryResource::collection($this->whenLoaded('statusHistory')),
            'shipment' => $this->whenLoaded('shipments', function () {
                // Ưu tiên vận đơn ĐÃ có phiếu/tem đã lưu (label_path) để đơn ở mọi trạng thái — kể cả đã giao /
                // hoàn / huỷ — vẫn lộ has_label=true ⇒ UI in lại được phiếu. Sau đó mới tới vận đơn chưa huỷ.
                $s = $this->shipments->first(fn ($x) => filled($x->label_path))
                    ?? $this->shipments->first(fn ($x) => $x->status !== 'cancelled')
                    ?? $this->shipments->first();

                if (! $s) {
                    return null;
                }
                // Tình trạng phiếu giao hàng per-đơn — tính trên các vận đơn OPEN (KHỚP CHÍNH XÁC
                // OrderController::applySlipFilter/by_slip; trước đây tính trên 1 vận đơn `$s` ưu tiên label_path
                // nên đơn có ≥2 vận đơn (vd 1 huỷ có tem + 1 open chưa tem) báo nhóm SAI so với bộ lọc).
                //   printable = có ≥1 vận đơn open đã có tem · loading = open chưa tem & đang queue (retry_at>now)
                //   · failed = open chưa tem & hết/không queue · null = không còn vận đơn open (đã giao/huỷ...).
                $open = $this->shipments->filter(fn ($x) => in_array((string) $x->status, Shipment::OPEN_STATUSES, true));
                $slipState = null;
                if ($open->isNotEmpty()) {
                    $slipState = $open->first(fn ($x) => filled($x->label_path)) !== null
                        ? 'printable'
                        : ($open->first(fn ($x) => $x->label_fetch_next_retry_at && $x->label_fetch_next_retry_at->isFuture()) !== null
                            ? 'loading'
                            : 'failed');
                }

                return [
                    'id' => $s->id, 'carrier' => $s->carrier, 'tracking_no' => $s->tracking_no,
                    'status' => $s->status,
                    // SPEC 0021 — nhãn tiếng Việt theo trạng thái vận đơn (vd `awaiting_pickup` → "Chờ lấy hàng").
                    'status_label' => Shipment::statusLabel((string) $s->status),
                    'label_url' => $s->label_url, 'has_label' => filled($s->label_path),
                    'slip_state' => $slipState,
                    'label_fetch_next_retry_at' => $s->label_fetch_next_retry_at?->toIso8601String(),
                    'label_unavailable' => (bool) data_get($s->raw, 'label_unavailable'),
                    'print_count' => (int) $s->print_count, 'last_printed_at' => $s->last_printed_at?->toIso8601String(),
                    'packed_at' => $s->packed_at?->toIso8601String(),
                ];
            }),
            // SPEC 2026-05-17 — đơn đã đẩy lên ĐVVC: có shipment với carrier ≠ 'manual' và status ngoài
            // pending/cancelled. UI dùng cờ này để cảnh báo "đơn đã đẩy ĐVVC — sửa chỉ áp dụng local".
            'is_pushed_to_carrier' => $this->whenLoaded('shipments', function () {
                return (bool) $this->shipments->first(function ($s) {
                    $carrier = (string) $s->carrier;
                    // 'manual' = tự vận chuyển; 'manual_ghn' / 'manual_ghtk'... = đơn manual đã đẩy ĐVVC thật.
                    $isRealCarrier = $carrier !== 'manual' && $carrier !== '';

                    return $isRealCarrier && ! in_array((string) $s->status, ['pending', 'cancelled'], true);
                });
            }),
            // Carrier code của shipment hiện active — dùng cho UI cảnh báo (vd "GHN", "manual_ghn").
            'pushed_carrier' => $this->whenLoaded('shipments', function () {
                $s = $this->shipments->first(fn ($x) => $x->status !== 'cancelled' && $x->status !== 'pending' && (string) $x->carrier !== 'manual' && (string) $x->carrier !== '');

                return $s ? (string) $s->carrier : null;
            }),
        ];
    }

    /**
     * The "Khách hàng" card (SPEC 0002 §6.1) — null when the order isn't linked to
     * a customer or the caller can't see customers. Read via the contract so Orders
     * doesn't depend on the Customer model.
     */
    private function customerCard(Request $request): ?array
    {
        if (! $this->customer_id || ! $request->user()?->can('customers.view')) {
            return null;
        }
        $withPhone = (bool) $request->user()->can('customers.view_phone');
        $profile = app(CustomerProfileContract::class)->findById((int) $this->tenant_id, (int) $this->customer_id, $withPhone);

        return $profile?->toOrderCard();
    }
}
