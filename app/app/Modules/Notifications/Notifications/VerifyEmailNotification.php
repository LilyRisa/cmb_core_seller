<?php

namespace CMBcoreSeller\Modules\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * Email xác thực tài khoản (SPEC 0022 §3.1).
 *
 * Signed URL Laravel TTL `auth.verification.expire` phút (mặc định 60). User click
 * link ⇒ controller `EmailVerificationController::verify` set `email_verified_at`
 * + fire `Verified` event ⇒ `SendWelcomeEmailOnVerified` listener gửi welcome.
 *
 * Queue `notifications`, tries 3, backoff 10/60/300s.
 */
class VerifyEmailNotification extends Notification implements ShouldQueue
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
        $url = $this->verificationUrl($notifiable);
        $brand = (string) system_setting('notifications.brand_name', config('notifications.brand.name', 'CMBcoreSeller'));
        $minutes = $this->expireMinutes();

        return (new MailMessage)
            ->subject("[{$brand}] Xác thực địa chỉ email")
            ->view('notifications::verify-email', [
                'user' => $notifiable,
                'verifyUrl' => $url,
                'expiresInMinutes' => $minutes,
                'expiresLabel' => $this->humanExpire($minutes),
            ]);
    }

    protected function verificationUrl(object $notifiable): string
    {
        return URL::temporarySignedRoute(
            'api.v1.auth.email.verify',
            Carbon::now()->addMinutes($this->expireMinutes()),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1((string) $notifiable->getEmailForVerification()),
            ]
        );
    }

    protected function expireMinutes(): int
    {
        return (int) config('auth.verification.expire', 60 * 24);
    }

    /** Nhãn thân thiện: "24 giờ" thay vì "1440 phút". */
    protected function humanExpire(int $minutes): string
    {
        return $minutes > 0 && $minutes % 60 === 0
            ? ($minutes / 60).' giờ'
            : $minutes.' phút';
    }
}
