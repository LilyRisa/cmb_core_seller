<?php

namespace CMBcoreSeller\Modules\Admin\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;

/**
 * Email cảnh báo admin (SPEC 2026-07-15) — 1 class dùng chung mọi loại thông báo. Gửi qua
 * on-demand routing tới email tự do (không phải Eloquent User) — xem
 * `AdminNotificationDispatcher::notify()`. Queue `notifications` (dùng chung queue mail
 * hiện có, không tạo queue mới). Dùng `MailMessage` fluent API — không cần đăng ký view
 * Blade riêng cho module Admin.
 */
class AdminAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @param array<string,mixed> $context */
    public function __construct(private readonly string $type, private readonly array $context)
    {
        $this->queue = (string) config('notifications.queue', 'notifications');
    }

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return match ($this->type) {
            NotificationTypeCatalog::SUPPORT_NEW_CONVERSATION => $this->supportMail(),
            NotificationTypeCatalog::AUTH_USER_VERIFIED => $this->userVerifiedMail(),
            'test' => $this->testMail(),
            default => throw new InvalidArgumentException("Unknown admin alert type: {$this->type}"),
        };
    }

    private function supportMail(): MailMessage
    {
        $tenantName = (string) ($this->context['tenant_name'] ?? '(không rõ shop)');
        $senderName = (string) ($this->context['sender_name'] ?? '(không rõ người gửi)');
        $snippet = (string) ($this->context['snippet'] ?? '');
        $conversationId = (int) ($this->context['conversation_id'] ?? 0);
        $appUrl = rtrim((string) config('notifications.frontend_url'), '/');

        return (new MailMessage)
            ->subject("[CMBcoreSeller] Khách nhắn CSKH mới — {$tenantName}")
            ->greeting('Có tin nhắn CSKH mới')
            ->line("Shop: {$tenantName}")
            ->line("Người gửi: {$senderName}")
            ->line("Nội dung: {$snippet}")
            ->action('Xem hội thoại', "{$appUrl}/admin/support-requests")
            ->line("Mã hội thoại: #{$conversationId}");
    }

    private function userVerifiedMail(): MailMessage
    {
        $name = (string) ($this->context['name'] ?? '');
        $email = (string) ($this->context['email'] ?? '');
        $tenantName = (string) ($this->context['tenant_name'] ?? '(chưa có shop)');

        return (new MailMessage)
            ->subject("[CMBcoreSeller] Người dùng mới đã xác minh email — {$name}")
            ->greeting('Người dùng mới đã đăng ký & xác minh email')
            ->line("Tên: {$name}")
            ->line("Email: {$email}")
            ->line("Shop: {$tenantName}");
    }

    private function testMail(): MailMessage
    {
        return (new MailMessage)
            ->subject('[CMBcoreSeller] Email test thông báo admin')
            ->line('Đây là email test — cấu hình nhận thông báo admin của bạn đang hoạt động đúng.');
    }
}
