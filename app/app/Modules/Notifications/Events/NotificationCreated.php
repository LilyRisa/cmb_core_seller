<?php

namespace CMBcoreSeller\Modules\Notifications\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Phát khi một thông báo in-app được tạo cho 1 user (SPEC 0036). Broadcast lên private
 * channel RIÊNG của user `tenant.{tenantId}.notifications.{userId}` (không lộ chéo
 * user/tenant). FE (`useNotificationsRealtime`) nghe `.notification.created` → invalidate
 * query để cập nhật chuông NGAY. Driver `null` (Reverb tắt) ⇒ no-op, FE rơi về polling.
 */
class NotificationCreated implements ShouldBroadcast
{
    use Dispatchable;

    public function __construct(
        public int $notificationId,
        public int $tenantId,
        public int $userId,
    ) {}

    /** @return list<PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("tenant.{$this->tenantId}.notifications.{$this->userId}")];
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    /** @return array<string,mixed> */
    public function broadcastWith(): array
    {
        return ['id' => $this->notificationId];
    }
}
