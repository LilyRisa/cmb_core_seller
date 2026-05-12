<?php

namespace CMBcoreSeller\Modules\Customers\Services;

use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Customers\Models\CustomerNote;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Anonymizes customer PII when a shop is disconnected or the marketplace requests
 * data deletion. `phone_hash` + `lifetime_stats` are kept (non-identifying
 * aggregates the seller still needs); `phone`/`name`/`email`/`addresses_meta` are
 * cleared. A customer that still has orders on *other* shops in the same tenant is
 * kept; only that shop's order-scoped notes are removed. See SPEC 0002 §8.
 */
class CustomerAnonymizer
{
    public const PLACEHOLDER = '[ANONYMIZED]';

    /** @return int number of customers anonymized */
    public function anonymizeForShop(int $tenantId, int $channelAccountId): int
    {
        $orderIds = Order::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->where('channel_account_id', $channelAccountId)
            ->pluck('id');
        if ($orderIds->isEmpty()) {
            return 0;
        }

        $customerIds = Order::withoutGlobalScope(TenantScope::class)
            ->whereIn('id', $orderIds)->whereNotNull('customer_id')
            ->distinct()->pluck('customer_id');

        $anonymized = 0;
        foreach ($customerIds as $cid) {
            /** @var Customer|null $customer */
            $customer = Customer::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenantId)->find($cid);
            if (! $customer || $customer->isAnonymized()) {
                continue;
            }

            $hasOtherOrders = Order::withoutGlobalScope(TenantScope::class)
                ->where('customer_id', $cid)->where('channel_account_id', '!=', $channelAccountId)->exists();

            DB::transaction(function () use ($customer, $orderIds, $hasOtherOrders, &$anonymized) {
                // Always drop notes tied to a deauthorized-shop order.
                CustomerNote::withoutGlobalScope(TenantScope::class)
                    ->where('customer_id', $customer->getKey())
                    ->whereIn('order_id', $orderIds)->delete();

                if (! $hasOtherOrders) {
                    $customer->forceFill([
                        'phone' => self::PLACEHOLDER,
                        'name' => null,
                        'email' => null,
                        'email_hash' => null,
                        'addresses_meta' => [],
                        'manual_note' => null,
                        'pii_anonymized_at' => now(),
                    ])->save();
                    $anonymized++;
                }
            });
        }

        Log::info('customers.anonymized_for_shop', ['tenant_id' => $tenantId, 'channel_account_id' => $channelAccountId, 'customers' => $anonymized]);

        return $anonymized;
    }
}
