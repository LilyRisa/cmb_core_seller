<?php

namespace CMBcoreSeller\Modules\Messaging\Console\Commands;

use CMBcoreSeller\Modules\Messaging\Contracts\ExpoPushSenderContract;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\PushSubscription;
use CMBcoreSeller\Modules\Messaging\Services\WebPushSender;
use CMBcoreSeller\Modules\Tenancy\Models\MobileDevice;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Gom tin nhắn mới → push cho user KHÔNG hoạt động (tab đóng/ẩn). Chạy mỗi 30'
 * qua scheduler. "Không hoạt động" = `last_seen_at` cũ hơn `--inactive-min` (heartbeat
 * từ FE giữ last_seen_at mới khi tab visible). Đếm hội thoại có inbound MỚI sau
 * `last_notified_at` ⇒ "N người nhắn tin mới"; gửi xong cập nhật last_notified_at.
 *
 * Hai kênh độc lập (SPEC 0029):
 *   - Web Push (VAPID) → trình duyệt SPA. Gate bằng WebPushSender::isConfigured().
 *   - Expo Push        → app mobile. Gate bằng ExpoPushSenderContract::isConfigured().
 * Mỗi kênh tự gate riêng — tắt 1 kênh không ảnh hưởng kênh kia.
 */
class PushNewMessageDigest extends Command
{
    protected $signature = 'messaging:push-digest {--inactive-min=5}';

    protected $description = 'Push gom tin nhắn mới (Web + Expo) cho user không hoạt động (mỗi 30 phút).';

    public function handle(WebPushSender $webSender, ExpoPushSenderContract $expoSender): int
    {
        $inactiveBefore = now()->subMinutes((int) $this->option('inactive-min'));

        $webSent = $webSender->isConfigured()
            ? $this->dispatchWebPush($webSender, $inactiveBefore)
            : $this->warnAndZero('push-digest: VAPID chưa cấu hình (Admin → Cấu hình → Thông báo).');

        $expoSent = $expoSender->isConfigured()
            ? $this->dispatchExpoPush($expoSender, $inactiveBefore)
            : 0;

        $this->info("push-digest: sent {$webSent} web + {$expoSent} expo notifications.");

        return self::SUCCESS;
    }

    private function warnAndZero(string $message): int
    {
        $this->warn($message);

        return 0;
    }

    /** Số inbound MỚI (sau $since) cho 1 tenant — dùng chung cho cả 2 kênh. */
    private function newInboundCount(int $tenantId, Carbon $since): int
    {
        return Conversation::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereNull('blocked_at')
            ->where('status', '!=', Conversation::STATUS_SPAM)
            ->whereNotNull('last_inbound_at')
            ->where('last_inbound_at', '>', $since)
            ->count();
    }

    private function dispatchWebPush(WebPushSender $sender, Carbon $inactiveBefore): int
    {
        $sent = 0;

        PushSubscription::query()
            ->where(fn ($q) => $q->whereNull('last_seen_at')->orWhere('last_seen_at', '<', $inactiveBefore))
            ->orderBy('id')
            ->chunkById(200, function ($subs) use ($sender, &$sent) {
                foreach ($subs as $sub) {
                    $since = $sub->last_notified_at ?? $sub->created_at ?? now()->subDay();

                    $count = $this->newInboundCount($sub->tenant_id, $since);
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

        return $sent;
    }

    private function dispatchExpoPush(ExpoPushSenderContract $sender, Carbon $inactiveBefore): int
    {
        $sent = 0;

        MobileDevice::query()
            ->where(fn ($q) => $q->whereNull('last_seen_at')->orWhere('last_seen_at', '<', $inactiveBefore))
            ->orderBy('id')
            ->chunkById(200, function ($devices) use ($sender, &$sent) {
                foreach ($devices as $device) {
                    $since = $device->last_notified_at ?? $device->created_at ?? now()->subDay();

                    $count = $this->newInboundCount($device->tenant_id, $since);
                    if ($count < 1) {
                        continue;
                    }

                    $ok = $sender->send($device, [
                        'title' => 'Tin nhắn mới',
                        'body' => 'Bạn có tin nhắn mới',
                        'data' => ['url' => '/messaging', 'count' => $count],
                    ]);

                    if ($ok) {
                        $device->forceFill(['last_notified_at' => now()])->save();
                        $sent++;
                    }
                }
            });

        return $sent;
    }
}
