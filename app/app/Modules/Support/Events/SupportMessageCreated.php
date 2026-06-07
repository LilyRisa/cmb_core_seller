<?php

namespace CMBcoreSeller\Modules\Support\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Phát khi có tin CSKH mới (user gửi / CSKH trả lời / đóng cuộc) — realtime widget Support (ADR-0021).
 *
 * Broadcast lên private channel `tenant.{id}.support`; FE (`lib/support.tsx` → useSupportRealtime)
 * subscribe để cập nhật badge chưa đọc + danh sách hội thoại NGAY thay vì poll 8-20s. No-op khi Reverb
 * tắt (FE polling fallback). `tenant_id` truyền thẳng (khỏi query lại như messaging).
 */
class SupportMessageCreated implements ShouldBroadcast
{
    use Dispatchable;

    public function __construct(
        public int $conversationId,
        public int $tenantId,
        public string $sender,
    ) {}

    /** @return list<PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("tenant.{$this->tenantId}.support")];
    }

    public function broadcastAs(): string
    {
        return 'support.message.created';
    }

    /** @return array<string,mixed> */
    public function broadcastWith(): array
    {
        return ['conversation_id' => $this->conversationId, 'sender' => $this->sender];
    }
}
