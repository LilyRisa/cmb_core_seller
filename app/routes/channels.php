<?php

use CMBcoreSeller\Modules\Messaging\Support\MessagingChannelAuthorizer;
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
