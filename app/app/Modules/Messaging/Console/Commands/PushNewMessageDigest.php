<?php

namespace CMBcoreSeller\Modules\Messaging\Console\Commands;

use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\PushSubscription;
use CMBcoreSeller\Modules\Messaging\Services\WebPushSender;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Console\Command;

/**
 * Gom tin nhắn mới → Web Push cho user KHÔNG hoạt động (tab đóng/ẩn). Chạy mỗi 30'
 * qua scheduler. "Không hoạt động" = `last_seen_at` cũ hơn `--inactive-min` (heartbeat
 * từ FE giữ last_seen_at mới khi tab visible). Đếm hội thoại có inbound MỚI sau
 * `last_notified_at` ⇒ "N người nhắn tin mới"; gửi xong cập nhật last_notified_at.
 */
class PushNewMessageDigest extends Command
{
    protected $signature = 'messaging:push-digest {--inactive-min=5}';

    protected $description = 'Web Push gom tin nhắn mới cho user không hoạt động (mỗi 30 phút).';

    public function handle(WebPushSender $sender): int
    {
        if (! $sender->isConfigured()) {
            $this->warn('push-digest: VAPID chưa cấu hình (Admin → Cấu hình → Thông báo).');

            return self::SUCCESS;
        }

        $inactiveBefore = now()->subMinutes((int) $this->option('inactive-min'));
        $sent = 0;

        PushSubscription::query()
            ->where(fn ($q) => $q->whereNull('last_seen_at')->orWhere('last_seen_at', '<', $inactiveBefore))
            ->orderBy('id')
            ->chunkById(200, function ($subs) use ($sender, &$sent) {
                foreach ($subs as $sub) {
                    $since = $sub->last_notified_at ?? $sub->created_at ?? now()->subDay();

                    $count = Conversation::withoutGlobalScope(TenantScope::class)
                        ->where('tenant_id', $sub->tenant_id)
                        ->whereNull('blocked_at')
                        ->where('status', '!=', Conversation::STATUS_SPAM)
                        ->whereNotNull('last_inbound_at')
                        ->where('last_inbound_at', '>', $since)
                        ->count();

                    if ($count < 1) {
                        continue;
                    }

                    $ok = $sender->send($sub, [
                        'title' => 'Tin nhắn mới',
                        'body' => "{$count} người nhắn tin mới",
                        'url' => '/messaging',
                    ]);

                    if ($ok) {
                        $sub->forceFill(['last_notified_at' => now()])->save();
                        $sent++;
                    }
                }
            });

        $this->info("push-digest: sent {$sent} notifications.");

        return self::SUCCESS;
    }
}
