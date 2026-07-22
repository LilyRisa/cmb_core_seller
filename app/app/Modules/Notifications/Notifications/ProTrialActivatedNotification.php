<?php

namespace CMBcoreSeller\Modules\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * Email xác nhận kích hoạt gói Pro trải nghiệm — gửi khi `ProTrialService::register()` thành công
 * (dù qua popup mời tự động hay nút tự phục vụ ở Cài đặt > Gói, cùng một code path).
 *
 * Queue `notifications`, tries 3, backoff 10/60/300s (đồng bộ các notification khác trong module).
 */
class ProTrialActivatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public Carbon $grantedAt, public Carbon $expiresAt)
    {
        $this->queue = (string) config('notifications.queue', 'notifications');
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $brand = (string) system_setting('notifications.brand_name', config('notifications.brand.name', 'CMBcoreSeller'));
        $appUrl = (string) config('notifications.frontend_url', config('app.url'));

        return (new MailMessage)
            ->subject("[{$brand}] Bạn đã được kích hoạt gói Pro trải nghiệm")
            ->view('notifications::pro-trial-activated', [
                'user' => $notifiable,
                'grantedAt' => $this->grantedAt->clone()->timezone(app_display_tz()),
                'expiresAt' => $this->expiresAt->clone()->timezone(app_display_tz()),
                'appUrl' => rtrim($appUrl, '/').'/',
            ]);
    }
}
