<?php

namespace CMBcoreSeller\Modules\Orders\Services;

use CMBcoreSeller\Modules\Orders\Contracts\OrderLookupContract;
use CMBcoreSeller\Modules\Orders\DTO\OrderSummary;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

class OrderLookupService implements OrderLookupContract
{
    public function recentByCustomer(int $tenantId, int $customerId, int $limit = 5): array
    {
        return Order::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->with('items:id,order_id,name,quantity')
            // Secondary `id` tie-break — trong test/burst-insert, `created_at` có thể trùng giây
            // (SQLite lưu datetime không mili-giây) khiến "mới nhất" không xác định nếu chỉ sort created_at.
            ->orderByDesc('created_at')->orderByDesc('id')
            ->limit(max(1, $limit))
            ->get()
            ->map(fn (Order $o) => OrderSummary::fromModel($o))
            ->all();
    }

    public function find(int $tenantId, int $orderId): ?OrderSummary
    {
        $order = Order::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereKey($orderId)
            ->with('items:id,order_id,name,quantity')
            ->first();

        return $order ? OrderSummary::fromModel($order) : null;
    }
}
