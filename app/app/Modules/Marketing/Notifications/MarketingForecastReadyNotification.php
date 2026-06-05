<?php

namespace CMBcoreSeller\Modules\Marketing\Notifications;

use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdForecast;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email báo cáo quảng cáo AI đã sẵn sàng (gửi cho Owner/Admin của tenant).
 * Queue `notifications`. Nội dung: dự báo 7 ngày + chiến lược + đánh giá creative.
 */
class MarketingForecastReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public AdAccount $account, public AdForecast $forecast)
    {
        $this->queue = (string) config('notifications.queue', 'notifications');
    }

    /** @return array<int,int> */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $brand = (string) system_setting('notifications.brand_name', config('notifications.brand.name', 'CMBcoreSeller'));

        return (new MailMessage)
            ->subject("[{$brand}] Báo cáo quảng cáo đã sẵn sàng")
            ->view('notifications::marketing-forecast-ready', [
                'account' => $this->account,
                'payload' => (array) $this->forecast->payload,
                'generatedAt' => $this->forecast->generated_at,
                'appUrl' => rtrim((string) config('app.url'), '/').'/marketing',
            ]);
    }
}
