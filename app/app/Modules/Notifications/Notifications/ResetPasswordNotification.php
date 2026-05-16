<?php

namespace CMBcoreSeller\Modules\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email đặt lại mật khẩu (SPEC 0022 §3.3).
 *
 * Token tạo bởi Password Broker (hash bcrypt vào `password_reset_tokens`),
 * TTL `auth.passwords.users.expire` phút (mặc định 60). URL trỏ tới SPA FE.
 *
 * Queue `notifications`, tries 3, backoff 10/60/300s.
 */
class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public string $token)
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
        $email = (string) ($notifiable->getEmailForPasswordReset() ?? $notifiable->email);
        $base = rtrim((string) config('notifications.frontend_url', config('app.url')), '/');
        $resetUrl = $base.'/password-reset?token='.$this->token.'&email='.urlencode($email);

        return (new MailMessage)
            ->subject("[{$brand}] Yêu cầu đặt lại mật khẩu")
            ->view('notifications::reset-password', [
                'user' => $notifiable,
                'resetUrl' => $resetUrl,
                'expiresInMinutes' => (int) config('auth.passwords.users.expire', 60),
            ]);
    }
}
