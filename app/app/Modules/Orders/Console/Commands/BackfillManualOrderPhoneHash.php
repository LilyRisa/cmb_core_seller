<?php

namespace CMBcoreSeller\Modules\Orders\Console\Commands;

use CMBcoreSeller\Modules\Customers\Support\CustomerPhoneNormalizer;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

/**
 * Backfill `orders.buyer_phone_hash`/`recipient_phone_hash` cho đơn thủ công tạo TRƯỚC khi 2 cột
 * này tồn tại (design 2026-07-14). Chạy tay 1 lần sau khi migrate cột — an toàn chạy lại nhiều lần
 * (idempotent: bỏ qua đơn đã có hash).
 */
class BackfillManualOrderPhoneHash extends Command
{
    protected $signature = 'orders:backfill-phone-hash';

    protected $description = 'Backfill buyer_phone_hash/recipient_phone_hash cho đơn thủ công (design 2026-07-14)';

    public function handle(): int
    {
        $count = 0;
        Order::withoutGlobalScope(TenantScope::class)
            ->where('source', 'manual')
            ->where(fn ($q) => $q->whereNull('buyer_phone_hash')->orWhereNull('recipient_phone_hash'))
            ->orderBy('id')
            ->chunkById(500, function (Collection $orders) use (&$count) {
                foreach ($orders as $order) {
                    $update = [];
                    if ($order->buyer_phone_hash === null) {
                        $update['buyer_phone_hash'] = CustomerPhoneNormalizer::normalizeAndHash($order->buyer_phone);
                    }
                    if ($order->recipient_phone_hash === null) {
                        $update['recipient_phone_hash'] = CustomerPhoneNormalizer::normalizeAndHash(
                            $order->shipping_address['phone'] ?? null
                        );
                    }
                    if ($update !== []) {
                        $order->forceFill($update)->save();
                        $count++;
                    }
                }
            });

        $this->info("Đã backfill hash cho {$count} đơn thủ công.");

        return self::SUCCESS;
    }
}
