<?php

namespace CMBcoreSeller\Modules\Notifications\Listeners;

use CMBcoreSeller\Modules\Channels\Events\ChannelAccountNeedsReconnect;
use CMBcoreSeller\Modules\Notifications\Services\NotificationDispatcher;
use CMBcoreSeller\Modules\Notifications\Support\NotificationType;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * SPEC 0036 — liên kết sàn/Facebook hết hiệu lực ⇒ thông báo in-app cho mọi thành viên
 * tenant. Dedup theo channel account ⇒ không spam mỗi 30' (job refresh token), tới khi
 * user đọc/kết nối lại.
 */
class NotifyOnChannelReconnect implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(private NotificationDispatcher $dispatcher) {}

    public function handle(ChannelAccountNeedsReconnect $event): void
    {
        $account = $event->account;
        $provider = $this->providerLabel((string) $account->provider);

        $this->dispatcher->dispatch((int) $account->tenant_id, [
            'type' => NotificationType::CHANNEL_RECONNECT_NEEDED,
            'level' => 'warning',
            'title' => "Liên kết {$provider} đã hết hiệu lực",
            'body' => "Gian hàng {$account->effectiveName()} cần được kết nối lại để tiếp tục đồng bộ.",
            'action_url' => '/channels',
            'data' => [
                'channel_account_id' => (int) $account->getKey(),
                'provider' => $account->provider,
                'reason' => $event->reason,
            ],
            'dedup_key' => 'channel.reconnect:'.$account->getKey(),
        ]);
    }

    /** Nhãn hiển thị thân thiện cho provider (không để core biết tên sàn — chỉ map hiển thị). */
    private function providerLabel(string $provider): string
    {
        return match ($provider) {
            'facebook_page' => 'Facebook',
            'tiktok' => 'TikTok Shop',
            'lazada', 'lazada_im' => 'Lazada',
            'shopee' => 'Shopee',
            default => ucfirst($provider),
        };
    }
}
