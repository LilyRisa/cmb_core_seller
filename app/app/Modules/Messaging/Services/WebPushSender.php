<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Modules\Messaging\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription as WebPushSubscription;
use Minishlink\WebPush\WebPush;

/**
 * Gửi Web Push qua minishlink/web-push. Khoá VAPID đọc từ `system_settings`
 * (sửa ở /admin/settings → nhóm "Thông báo"), fallback env. Best-effort: lỗi
 * ⇒ log + false; subscription hết hạn (404/410) ⇒ xoá row.
 */
class WebPushSender
{
    public function publicKey(): string
    {
        return (string) system_setting('push.vapid_public_key', (string) env('VAPID_PUBLIC_KEY', ''));
    }

    private function privateKey(): string
    {
        return (string) system_setting('push.vapid_private_key', (string) env('VAPID_PRIVATE_KEY', ''));
    }

    private function subject(): string
    {
        return (string) system_setting('push.vapid_subject', (string) env('VAPID_SUBJECT', ''));
    }

    /** Đã cấu hình đủ VAPID (public + private + subject)? */
    public function isConfigured(): bool
    {
        return $this->publicKey() !== '' && $this->privateKey() !== '' && $this->subject() !== '';
    }

    /**
     * Gửi 1 push. Trả true nếu OK. Sub hết hạn (404/410) ⇒ xoá + false.
     *
     * @param  array<string,mixed>  $payload  vd ['title'=>..,'body'=>..,'url'=>..]
     */
    public function send(PushSubscription $sub, array $payload): bool
    {
        if (! $this->isConfigured() || ! class_exists(WebPush::class)) {
            return false;
        }

        try {
            $webPush = new WebPush(['VAPID' => [
                'subject' => $this->subject(),
                'publicKey' => $this->publicKey(),
                'privateKey' => $this->privateKey(),
            ]]);

            $report = $webPush->sendOneNotification(
                WebPushSubscription::create([
                    'endpoint' => $sub->endpoint,
                    'keys' => ['p256dh' => $sub->p256dh, 'auth' => $sub->auth],
                ]),
                json_encode($payload, JSON_UNESCAPED_UNICODE) ?: '{}',
            );

            if ($report->isSubscriptionExpired()) {
                $sub->delete();

                return false;
            }
            if (! $report->isSuccess()) {
                Log::warning('messaging.push.failed', ['endpoint' => $sub->endpoint]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('messaging.push.exception', ['endpoint' => $sub->endpoint, 'error' => $e->getMessage()]);

            return false;
        }
    }
}
