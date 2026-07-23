<?php

namespace CMBcoreSeller\Modules\Notifications\Listeners;

use CMBcoreSeller\Modules\Inventory\Events\StockPushed;
use CMBcoreSeller\Modules\Notifications\Services\NotificationDispatcher;
use CMBcoreSeller\Modules\Notifications\Support\NotificationType;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Plan A (2026-07-23) — đẩy tồn kho lên sàn thất bại ⇒ thông báo in-app tab "Hệ thống".
 * Dedup theo channel_listing id ⇒ không spam mỗi lần job PushStockToListing retry, tới
 * khi user đọc/đẩy lại thành công (đọc rồi thì event fail tiếp sẽ tạo lại — hành vi dedup
 * chuẩn của NotificationDispatcher).
 */
class NotifyOnStockPushFailed implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(private NotificationDispatcher $dispatcher) {}

    public function handle(StockPushed $event): void
    {
        if ($event->ok) {
            return;
        }

        $listing = $event->listing;
        $name = $listing->title ?: $listing->seller_sku;

        $this->dispatcher->dispatch((int) $listing->tenant_id, [
            'type' => NotificationType::INVENTORY_STOCK_PUSH_FAILED,
            'level' => 'warning',
            'title' => "Đẩy tồn kho \"{$name}\" lên sàn thất bại",
            'body' => $listing->sync_error ?: 'Vui lòng kiểm tra lại kết nối gian hàng và thử đẩy tồn lại.',
            'action_url' => '/inventory',
            'data' => [
                'channel_listing_id' => (int) $listing->getKey(),
                'seller_sku' => $listing->seller_sku,
                'desired_qty' => $event->desired,
            ],
            'dedup_key' => 'inventory.stock_push_failed:'.$listing->getKey(),
        ]);
    }
}
