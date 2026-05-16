<?php

namespace CMBcoreSeller\Modules\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email chào mừng — gửi sau khi user verify email thành công (SPEC 0022 §3.1).
 *
 * Queue `notifications`, tries 3, backoff 10/60/300s.
 */
class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct()
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
        $brand = (string) config('notifications.brand.name', 'CMBcoreSeller');
        $appUrl = (string) config('notifications.frontend_url', config('app.url'));

        return (new MailMessage)
            ->subject("[{$brand}] Chào mừng bạn đến với {$brand}!")
            ->view('notifications::welcome', [
                'user' => $notifiable,
                'appUrl' => rtrim($appUrl, '/').'/',
            ]);
    }
}
