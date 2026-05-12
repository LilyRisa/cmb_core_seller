<?php

namespace CMBcoreSeller\Modules\Orders\Http\Resources;

use CMBcoreSeller\Modules\Customers\Contracts\CustomerProfileContract;
use CMBcoreSeller\Modules\Orders\Models\Order;
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
        $itemsCount = $this->relationLoaded('items')
            ? $this->items->sum('quantity')
            : ($this->items_count ?? null);

        return [
            'customer' => $this->customerCard($request),
            'id' => $this->id,
            'source' => $this->source,
            'channel_account_id' => $this->channel_account_id,
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
            'grand_total' => $this->grand_total,
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
