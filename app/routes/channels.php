<?php

use CMBcoreSeller\Modules\Messaging\Support\MessagingChannelAuthorizer;
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
