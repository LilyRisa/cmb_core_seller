<?php

use CMBcoreSeller\Modules\Messaging\Support\MessagingChannelAuthorizer;
use CMBcoreSeller\Modules\Notifications\Support\NotificationChannelAuthorizer;
use CMBcoreSeller\Modules\Support\Support\SupportChannelAuthorizer;
use Illuminate\Support\Facades\Broadcast;

/**
 * Realtime broadcast channels (ADR-0021 — Reverb).
 *
 * Inbox messaging realtime: private channel `tenant.{tenantId}.messaging`. Events
 * MessageReceived / MessageSent / ConversationCreated broadcast lên đây (xem
 * Modules/Messaging/Events + BroadcastsOnTenantChannel). FE (lib/echo.ts) subscribe.
 *
 * Authz tách ra {@see MessagingChannelAuthorizer} để test được: user PHẢI là thành
 * viên tenant + có quyền `messaging.view`. Sai ⇒ false ⇒ Echo từ chối join (chống
 * lộ tin nhắn tenant khác).
 */
Broadcast::channel('tenant.{tenantId}.messaging', function ($user, int $tenantId): bool {
    return app(MessagingChannelAuthorizer::class)->canViewTenantMessaging($user, $tenantId);
});

/**
 * Support/CSKH realtime: private channel `tenant.{tenantId}.support`. Event SupportMessageCreated
 * broadcast lên đây (user gửi / CSKH trả lời / đóng cuộc). FE (lib/support.tsx useSupportRealtime)
 * subscribe để cập nhật badge + hội thoại NGAY. Authz {@see SupportChannelAuthorizer}: chỉ cần là
 * thành viên tenant (support không gate quyền riêng).
 */
Broadcast::channel('tenant.{tenantId}.support', function ($user, int $tenantId): bool {
    return app(SupportChannelAuthorizer::class)->canViewTenantSupport($user, $tenantId);
});

/**
 * Thông báo in-app realtime (SPEC 0036): private channel RIÊNG từng user
 * `tenant.{tenantId}.notifications.{userId}`. Event NotificationCreated broadcast lên đây;
 * FE (lib/notifications useNotificationsRealtime) subscribe để cập nhật chuông NGAY. Authz
 * {@see NotificationChannelAuthorizer}: user PHẢI là thành viên tenant + chỉ nghe channel
 * của CHÍNH MÌNH (userId === auth id) — chống lộ chéo user/tenant.
 */
Broadcast::channel('tenant.{tenantId}.notifications.{userId}', function ($user, int $tenantId, int $userId): bool {
    return app(NotificationChannelAuthorizer::class)->canListen($user, $tenantId, $userId);
});
