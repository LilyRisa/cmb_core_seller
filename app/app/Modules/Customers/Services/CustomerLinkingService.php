<?php

namespace CMBcoreSeller\Modules\Customers\Services;

use CMBcoreSeller\Modules\Customers\Events\CustomerLinked;
use CMBcoreSeller\Modules\Customers\Events\CustomerReputationChanged;
use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Customers\Models\CustomerNote;
use CMBcoreSeller\Modules\Customers\Support\CustomerPhoneNormalizer;
use CMBcoreSeller\Modules\Customers\Support\ReputationCalculator;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Support\Facades\DB;

/**
 * Matches an order to a customer (by normalized phone hash), keeps lifetime stats
 * + reputation + auto-notes in sync. Idempotent: recompute reads straight from
 * `orders` (single source of truth) — never accumulates deltas. Operates with the
 * tenant scope disabled and an explicit tenant_id, because it runs from a queued
 * listener with no "current tenant". See SPEC 0002 §4.
 */
class CustomerLinkingService
{
    /** Match/create the customer for an order and refresh everything. Returns null if the phone can't be normalized. */
    public function linkOrder(Order $order): ?Customer
    {
        $tenantId = (int) $order->tenant_id;
        $phone = CustomerPhoneNormalizer::normalize($order->shipping_address['phone'] ?? null);
        if ($phone === null) {
            return null; // masked / missing — leave orders.customer_id as is
        }
        $hash = CustomerPhoneNormalizer::hash($phone);
        $placedAt = $order->placed_at ?: $order->created_at ?: now();

        return DB::transaction(function () use ($order, $tenantId, $phone, $hash, $placedAt) {
            /** @var Customer|null $customer */
            $customer = Customer::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)->where('phone_hash', $hash)
                ->lockForUpdate()->first();

            $created = false;
            if (! $customer) {
                $customer = new Customer;
                $customer->forceFill([
                    'tenant_id' => $tenantId,
                    'phone_hash' => $hash,
                    'phone' => $phone,
                    'name' => $order->buyer_name ?: null,
                    'addresses_meta' => $this->mergeAddresses([], $order->shipping_address),
                    'lifetime_stats' => $this->zeroStats(),
                    'tags' => [],
                    'reputation_score' => 100,
                    'reputation_label' => Customer::LABEL_OK,
                    'first_seen_at' => $placedAt,
                    'last_seen_at' => $placedAt,
                ])->save();
                $created = true;
            } else {
                $customer->forceFill([
                    'last_seen_at' => $customer->last_seen_at->gt($placedAt) ? $customer->last_seen_at : $placedAt,
                    'name' => $customer->name ?: ($order->buyer_name ?: null),
                    'phone' => $customer->isAnonymized() ? $customer->phone : $phone,
                    'addresses_meta' => $customer->isAnonymized() ? $customer->addresses_meta : $this->mergeAddresses($customer->addresses_meta ?? [], $order->shipping_address),
                ])->save();
            }

            // Link the order back (only if not already linked to someone).
            Order::withoutGlobalScope(TenantScope::class)
                ->whereKey($order->getKey())->whereNull('customer_id')
                ->update(['customer_id' => $customer->getKey()]);

            $this->recompute($customer);

            CustomerLinked::dispatch($customer, $order, $created);

            return $customer;
        });
    }

    /** Recompute lifetime stats + reputation + auto-notes from the order table. Safe to call repeatedly. */
    public function recompute(Customer $customer): void
    {
        $stats = $this->computeStats($customer);

        $fromLabel = $customer->reputation_label;
        $fromScore = (int) $customer->reputation_score;
        $rep = ReputationCalculator::evaluate($stats, (bool) $customer->is_blocked);

        $tags = array_values($customer->tags ?? []);
        if ($rep['is_vip'] && ! in_array('vip', $tags, true)) {
            $tags[] = 'vip';
        }

        $customer->forceFill([
            'lifetime_stats' => $stats,
            'reputation_score' => $rep['score'],
            'reputation_label' => $rep['label'],
            'tags' => $tags,
        ])->save();

        if ($rep['label'] !== $fromLabel) {
            CustomerReputationChanged::dispatch($customer, $fromLabel, $rep['label'], $fromScore, $rep['score']);
        }

        $this->maybeAddAutoNotes($customer, $stats);
    }

    /** @return array<string,int|string> */
    public function computeStats(Customer $customer): array
    {
        $cs = implode(',', array_map(fn ($v) => "'{$v}'", [StandardOrderStatus::Completed->value, StandardOrderStatus::Delivered->value]));
        $agg = Order::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $customer->tenant_id)
            ->where('customer_id', $customer->getKey())
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) AS total,
                SUM(CASE WHEN status IN ({$cs}) THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS cancelled,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS returned,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS deliv_failed,
                SUM(CASE WHEN status IN ({$cs}) THEN grand_total ELSE 0 END) AS revenue,
                MAX(id) AS last_id
            ", [
                StandardOrderStatus::Cancelled->value,
                StandardOrderStatus::ReturnedRefunded->value,
                StandardOrderStatus::DeliveryFailed->value,
            ])
            ->first();

        $total = (int) ($agg->total ?? 0);
        $completed = (int) ($agg->completed ?? 0);
        $cancelled = (int) ($agg->cancelled ?? 0);
        $returned = (int) ($agg->returned ?? 0);
        $delivFailed = (int) ($agg->deliv_failed ?? 0);
        $inProgress = max(0, $total - $completed - $cancelled - $returned - $delivFailed);
        $revenue = (int) ($agg->revenue ?? 0);
        $lastId = (int) ($agg->last_id ?? 0);
        $lastStatus = $lastId > 0
            ? (string) (Order::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $customer->tenant_id)->where('id', $lastId)->value('status') ?? '')
            : '';

        return [
            'orders_total' => $total,
            'orders_completed' => $completed,
            'orders_cancelled' => $cancelled,
            'orders_returned' => $returned,
            'orders_delivery_failed' => $delivFailed,
            'orders_in_progress' => $inProgress,
            'revenue_completed' => $revenue,
            'last_order_id' => $lastId,
            'last_order_status' => $lastStatus,
            'computed_at' => now()->toIso8601String(),
        ];
    }

    /** @return array<string,int|string> */
    private function zeroStats(): array
    {
        return [
            'orders_total' => 0, 'orders_completed' => 0, 'orders_cancelled' => 0,
            'orders_returned' => 0, 'orders_delivery_failed' => 0, 'orders_in_progress' => 0,
            'revenue_completed' => 0, 'last_order_id' => 0, 'last_order_status' => '',
            'computed_at' => now()->toIso8601String(),
        ];
    }

    /** @param array<string,int|string> $stats */
    private function maybeAddAutoNotes(Customer $customer, array $stats): void
    {
        $cfg = (array) config('customers.auto_notes', []);
        $add = function (string $kind, string $severity, string $text, string $bucket) use ($customer) {
            CustomerNote::withoutGlobalScope(TenantScope::class)->firstOrCreate(
                ['customer_id' => $customer->getKey(), 'dedupe_key' => "{$kind}:{$bucket}"],
                ['tenant_id' => $customer->tenant_id, 'kind' => $kind, 'severity' => $severity, 'note' => $text, 'created_at' => now()],
            );
        };

        $cancelled = (int) ($stats['orders_cancelled'] ?? 0);
        if (($cfg['cancel_danger_at'] ?? 5) > 0 && $cancelled >= ($cfg['cancel_danger_at'] ?? 5)) {
            $add('auto.cancel_streak', CustomerNote::SEV_DANGER, "Đã có {$cancelled} đơn huỷ — cân nhắc chặn khách.", 'danger');
        } elseif (($cfg['cancel_warning_at'] ?? 2) > 0 && $cancelled >= ($cfg['cancel_warning_at'] ?? 2)) {
            $add('auto.cancel_streak', CustomerNote::SEV_WARNING, "Đã có {$cancelled} đơn huỷ — kiểm tra kỹ đơn mới.", 'warning');
        }

        $delivFailed = (int) ($stats['orders_delivery_failed'] ?? 0);
        if (($cfg['delivery_failed_warning_at'] ?? 2) > 0 && $delivFailed >= ($cfg['delivery_failed_warning_at'] ?? 2)) {
            $add('auto.delivery_failed', CustomerNote::SEV_WARNING, "{$delivFailed} lần giao thất bại — gọi xác nhận trước khi ship.", 'warning');
        }

        $returned = (int) ($stats['orders_returned'] ?? 0);
        if (($cfg['return_warning_at'] ?? 3) > 0 && $returned >= ($cfg['return_warning_at'] ?? 3)) {
            $add('auto.return_streak', CustomerNote::SEV_WARNING, "{$returned} lần trả hàng — sản phẩm có vấn đề?", 'warning');
        }

        $completed = (int) ($stats['orders_completed'] ?? 0);
        if (($cfg['vip_at'] ?? 10) > 0 && $completed >= ($cfg['vip_at'] ?? 10)) {
            $add('auto.vip', CustomerNote::SEV_INFO, "Khách VIP — đã đặt {$completed} đơn thành công.", 'vip');
        }
    }

    /**
     * @param  array<int,mixed>  $existing  previously-seen shipping addresses (json-cast)
     * @param  array<string,mixed>|null  $address
     * @return list<array<array-key,mixed>>
     */
    private function mergeAddresses(array $existing, ?array $address): array
    {
        $existingArrays = array_values(array_filter($existing, 'is_array'));
        if (! $address) {
            return $existingArrays;
        }
        $key = fn (array $a) => mb_strtolower(trim((string) (($a['address'] ?? $a['detail'] ?? '').'|'.($a['ward'] ?? '').'|'.($a['district'] ?? '').'|'.($a['city'] ?? $a['province'] ?? ''))));
        $merged = [$address];
        foreach ($existingArrays as $a) {
            if ($key($a) !== $key($address)) {
                $merged[] = $a;
            }
        }
        $max = (int) config('customers.max_addresses', 5);

        return array_slice($merged, 0, max(1, $max));
    }
}
