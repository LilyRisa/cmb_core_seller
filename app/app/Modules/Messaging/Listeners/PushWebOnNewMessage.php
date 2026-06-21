<?php

namespace CMBcoreSeller\Modules\Messaging\Listeners;

use CMBcoreSeller\Modules\Messaging\Events\MessageReceived;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\PushSubscription;
use CMBcoreSeller\Modules\Messaging\Services\WebPushSender;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Web Push NGAY khi có tin nhắn inbound mới (event-driven) — bù cho `messaging:push-digest` (gom mỗi 30').
 *
 * Chỉ gửi cho subscription KHÔNG hoạt động (tab đóng/ẩn — `last_seen_at` cũ hơn ACTIVE_WINDOW giây): user
 * đang mở tab đã thấy thông báo in-app qua Reverb nên không cần push (tránh trùng). Throttle THROTTLE giây
 * / sub để khách nhắn liên tiếp không tạo loạt push — sw.js gộp theo `tag` nên người dùng thấy 1 thông báo
 * tự cập nhật. Best-effort: `WebPushSender::send` tự nuốt lỗi + xoá sub hết hạn.
 *
 * Queued trên `messaging-bg` (PHẢI có supervisor cùng tên trong Horizon — nếu không job kẹt im lặng).
 * `last_notified_at` cập nhật khi gửi ⇒ digest 30' không push lại đúng tin đã báo (2 cơ chế bổ trợ nhau).
 */
class PushWebOnNewMessage implements ShouldQueue
{
    public string $queue = 'messaging-bg';

    /** Sub có `last_seen_at` trong ACTIVE_WINDOW giây = đang mở tab (heartbeat 60s/lần) ⇒ bỏ qua. */
    private const ACTIVE_WINDOW = 90;

    /** Không push lại cùng 1 sub trong THROTTLE giây (chống spam khi khách nhắn dồn). */
    private const THROTTLE = 20;

    public function __construct(private readonly WebPushSender $sender) {}

    public function handle(MessageReceived $event): void
    {
        if (! $this->sender->isConfigured()) {
            return;
        }
        $conv = Conversation::withoutGlobalScope(TenantScope::class)->find($event->conversationId);
        if (! $conv || $conv->blocked_at !== null || $conv->status === Conversation::STATUS_SPAM) {
            return; // hội thoại đã chặn / spam ⇒ không làm phiền
        }

        $body = $conv->buyer_name ? $conv->buyer_name.' vừa nhắn tin' : 'Bạn có tin nhắn mới';
        $activeBefore = now()->subSeconds(self::ACTIVE_WINDOW);
        $throttleBefore = now()->subSeconds(self::THROTTLE);

        PushSubscription::query()
            ->where('tenant_id', (int) $conv->tenant_id)
            ->where(fn ($q) => $q->whereNull('last_seen_at')->orWhere('last_seen_at', '<', $activeBefore))
            ->where(fn ($q) => $q->whereNull('last_notified_at')->orWhere('last_notified_at', '<', $throttleBefore))
            ->orderBy('id')
            ->chunkById(200, function ($subs) use ($body) {
                foreach ($subs as $sub) {
                    if ($this->sender->send($sub, ['title' => 'Tin nhắn mới', 'body' => $body, 'url' => '/messaging'])) {
                        $sub->forceFill(['last_notified_at' => now()])->save();
                    }
                }
            });
    }
}
