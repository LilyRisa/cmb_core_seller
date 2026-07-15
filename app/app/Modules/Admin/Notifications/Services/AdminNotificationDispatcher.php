<?php

namespace CMBcoreSeller\Modules\Admin\Notifications\Services;

use CMBcoreSeller\Modules\Admin\Models\AdminNotificationRecipient;
use CMBcoreSeller\Modules\Admin\Notifications\AdminAlertNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Gửi 1 loại thông báo tới mọi email admin đã bật (SPEC 2026-07-15). $emails rỗng ⇒
 * no-op hợp lệ (chưa cấu hình ai nhận loại này).
 */
class AdminNotificationDispatcher
{
    /** @param array<string,mixed> $context truyền thẳng vào AdminAlertNotification */
    public function notify(string $type, array $context): void
    {
        $emails = AdminNotificationRecipient::query()
            ->active()
            ->whereHas('subscriptions', fn ($q) => $q->where('notification_type', $type))
            ->pluck('email');

        foreach ($emails as $email) {
            Notification::route('mail', $email)->notify(new AdminAlertNotification($type, $context));
        }
    }
}
