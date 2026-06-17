<?php

namespace CMBcoreSeller\Modules\Customers\Services;

use CMBcoreSeller\Modules\Customers\Contracts\CustomerReportContract;
use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Customers\Models\CustomerReport;
use CMBcoreSeller\Modules\Customers\Support\CustomerPhoneNormalizer;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Validation\ValidationException;

/**
 * Tạo & tra report "bom hàng" nội bộ từ đơn thủ công bị hoàn (SPEC 0038 v2).
 * Mỗi đơn chỉ báo 1 lần (`unique(order_id)`).
 */
class CustomerReportService implements CustomerReportContract
{
    /** Trạng thái hoàn/thất bại được phép báo cáo. */
    public const REPORTABLE_STATUSES = [
        StandardOrderStatus::DeliveryFailed->value,
        StandardOrderStatus::Returning->value,
        StandardOrderStatus::ReturnedRefunded->value,
    ];

    /** Đơn có đủ điều kiện báo cáo? (đơn thủ công + trạng thái hoàn/thất bại) */
    public static function isReportable(Order $order): bool
    {
        return $order->source === 'manual'
            && in_array($order->status->value, self::REPORTABLE_STATUSES, true);
    }

    /**
     * Tạo report từ đơn. Ném ValidationException nếu không đủ điều kiện hoặc đã báo.
     */
    public function createFromOrder(Order $order, string $reason, ?int $userId): CustomerReport
    {
        if (! self::isReportable($order)) {
            throw ValidationException::withMessages(['order_id' => 'Chỉ báo cáo được đơn thủ công đã hoàn / giao thất bại.']);
        }

        $hash = $this->orderPhoneHash($order);
        if ($hash === null) {
            throw ValidationException::withMessages(['order_id' => 'Đơn không có số điện thoại hợp lệ để báo cáo.']);
        }

        if (CustomerReport::query()->where('order_id', $order->getKey())->exists()) {
            throw ValidationException::withMessages(['order_id' => 'Đơn này đã được báo cáo bom hàng.']);
        }

        return CustomerReport::query()->create([
            'tenant_id' => $order->tenant_id,
            'phone_hash' => $hash,
            'order_id' => $order->getKey(),
            'order_number' => $order->order_number,
            'reason' => $reason,
            'reported_by_user_id' => $userId,
            'reported_at' => now(),
        ]);
    }

    public function isOrderReported(int $tenantId, int $orderId): bool
    {
        return CustomerReport::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('order_id', $orderId)
            ->exists();
    }

    /** SĐT-hash của đơn: ưu tiên khách đã liên kết, thiếu thì từ buyer_phone. */
    private function orderPhoneHash(Order $order): ?string
    {
        if ($order->customer_id) {
            $hash = Customer::query()->whereKey($order->customer_id)->value('phone_hash');
            if ($hash) {
                return (string) $hash;
            }
        }

        return CustomerPhoneNormalizer::normalizeAndHash($order->buyer_phone);
    }
}
