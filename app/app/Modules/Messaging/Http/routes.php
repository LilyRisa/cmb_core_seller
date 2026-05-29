<?php

use CMBcoreSeller\Modules\Messaging\Http\Controllers\AdminAiProviderController;
use CMBcoreSeller\Modules\Messaging\Http\Controllers\AiSuggestionController;
use CMBcoreSeller\Modules\Messaging\Http\Controllers\AutomationFlowController;
use CMBcoreSeller\Modules\Messaging\Http\Controllers\AutoReplyRuleController;
use CMBcoreSeller\Modules\Messaging\Http\Controllers\ConversationController;
use CMBcoreSeller\Modules\Messaging\Http\Controllers\FacebookCommentController;
use CMBcoreSeller\Modules\Messaging\Http\Controllers\FacebookOAuthController;
use CMBcoreSeller\Modules\Messaging\Http\Controllers\KnowledgeController;
use CMBcoreSeller\Modules\Messaging\Http\Controllers\MessageController;
use CMBcoreSeller\Modules\Messaging\Http\Controllers\MessagingChannelController;
use CMBcoreSeller\Modules\Messaging\Http\Controllers\MessagingSettingsController;
use CMBcoreSeller\Modules\Messaging\Http\Controllers\PushSubscriptionController;
use CMBcoreSeller\Modules\Messaging\Http\Controllers\TagController;
use CMBcoreSeller\Modules\Messaging\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;

/*
 |--------------------------------------------------------------------------
 | Messaging REST API routes (SPEC-0024 — Phase 7.x đề xuất).
 |--------------------------------------------------------------------------
 |
 | Webhook routes (no CSRF, no auth) sống ở `routes/webhook.php` toàn cục
 | (cùng pattern Channels). File này chỉ chứa REST `/api/v1/messaging/*`.
 |
 | Middleware group: Sanctum + verified + tenant + plan.over_quota_lock +
 | plan.feature:messaging_inbox.
 |
 | Permissions (xem `app/Modules/Tenancy/Enums/Role.php`):
 |   - `messaging.view`        — list, show, read, settings
 |   - `messaging.reply`       — send tin (text/template/ai-accept)
 |   - `messaging.assign`      — gán conversation cho NV (owner/admin)
 |   - `messaging.template.manage`  — CRUD template (S3)
 |   - `messaging.rule.manage`      — CRUD auto-reply rule (S5)
 |   - `messaging.ai.config`        — chọn AI provider tenant (S6)
 |   - `messaging.ai.train`         — upload knowledge doc (S6)
 |
 | Permission check ở Controller qua `Gate::authorize()` — đã có `Gate::before`
 | set ở Tenancy reading role.permissions().
 |
 | Rate limit: per user 30 msg/phút (`throttle:30,1`) chặt cho send để chống
 | gửi nhầm. Per tenant rate limit per shop wired ở `MessageSendService` (S2).
 |
 | S1 chỉ list/show/read/update + sendText/sendTemplate. Media upload (S2) +
 | AI suggestion (S6) + auto-reply rules CRUD (S5) sẽ thêm sau.
 */

Route::middleware(['api', 'auth:sanctum', 'verified', 'tenant', 'plan.over_quota_lock', 'plan.feature:messaging_inbox'])
    ->prefix('api/v1/messaging')->group(function () {
        Route::get('conversations', [ConversationController::class, 'index'])
            ->name('messaging.conversations.index');
        Route::get('conversations/{id}', [ConversationController::class, 'show'])
            ->whereNumber('id')->name('messaging.conversations.show');
        Route::post('conversations/{id}/read', [ConversationController::class, 'markRead'])
            ->whereNumber('id')->name('messaging.conversations.read');
        Route::post('conversations/{id}/unread', [ConversationController::class, 'markUnread'])
            ->whereNumber('id')->name('messaging.conversations.unread');
        Route::post('conversations/{id}/block', [ConversationController::class, 'block'])
            ->whereNumber('id')->name('messaging.conversations.block');      // messaging.reply
        Route::delete('conversations/{id}/block', [ConversationController::class, 'unblock'])
            ->whereNumber('id')->name('messaging.conversations.unblock');    // messaging.reply
        Route::patch('conversations/{id}', [ConversationController::class, 'update'])
            ->whereNumber('id')->name('messaging.conversations.update');
        // Gắn đơn vừa tạo (từ khung chat) vào hội thoại ⇒ hiện icon đơn ở danh sách.
        Route::post('conversations/{id}/link-order', [ConversationController::class, 'linkOrder'])
            ->whereNumber('id')->name('messaging.conversations.link-order');

        // Send tin — rate limit chặt
        Route::middleware('throttle:30,1')->group(function () {
            Route::post('conversations/{id}/messages', [MessageController::class, 'sendText'])
                ->whereNumber('id')->name('messaging.messages.send');
            Route::post('conversations/{id}/messages/template', [MessageController::class, 'sendTemplate'])
                ->whereNumber('id')->name('messaging.messages.template');
            Route::post('conversations/{id}/messages/media', [MessageController::class, 'sendMedia'])
                ->whereNumber('id')->name('messaging.messages.media');

            // --- AI suggestion (S6) — gate thêm plan.feature:messaging_ai (Business) ---
            // + rate-limit ai-suggestion theo tenant (20/phút, định nghĩa ở AppServiceProvider).
            Route::middleware(['plan.feature:messaging_ai', 'throttle:ai-suggestion'])->group(function () {
                Route::post('conversations/{id}/ai-suggestion', [AiSuggestionController::class, 'generate'])
                    ->whereNumber('id')->name('messaging.ai.suggestion.generate');
                Route::post('conversations/{id}/ai-suggestion/{draftId}/accept', [AiSuggestionController::class, 'accept'])
                    ->whereNumber('id')->whereNumber('draftId')->name('messaging.ai.suggestion.accept');
                Route::delete('conversations/{id}/ai-suggestion/{draftId}', [AiSuggestionController::class, 'reject'])
                    ->whereNumber('id')->whereNumber('draftId')->name('messaging.ai.suggestion.reject');
            });
        });

        // --- Templates (S3) — CRUD mẫu tin ---------------------------------
        Route::get('templates', [TemplateController::class, 'index'])
            ->name('messaging.templates.index');
        Route::get('templates/{id}', [TemplateController::class, 'show'])
            ->whereNumber('id')->name('messaging.templates.show');
        Route::post('templates', [TemplateController::class, 'store'])
            ->name('messaging.templates.store');
        Route::patch('templates/{id}', [TemplateController::class, 'update'])
            ->whereNumber('id')->name('messaging.templates.update');
        Route::delete('templates/{id}', [TemplateController::class, 'destroy'])
            ->whereNumber('id')->name('messaging.templates.destroy');

        // --- AI training knowledge docs (S6) -------------------------------
        Route::get('knowledge-docs', [KnowledgeController::class, 'index'])
            ->name('messaging.knowledge.index');
        Route::post('knowledge-docs', [KnowledgeController::class, 'store'])
            ->name('messaging.knowledge.store');
        Route::delete('knowledge-docs/{id}', [KnowledgeController::class, 'destroy'])
            ->whereNumber('id')->name('messaging.knowledge.destroy');

        // --- Tenant messaging settings (S6) — chọn AI provider, away hours --
        Route::get('settings', [MessagingSettingsController::class, 'show'])
            ->name('messaging.settings.show');
        Route::patch('settings', [MessagingSettingsController::class, 'update'])
            ->name('messaging.settings.update');

        // --- Facebook Page OAuth connect (S2) — trả authorize URL ---
        Route::post('facebook/connect', [FacebookOAuthController::class, 'start'])
            ->name('messaging.facebook.connect');

        // --- Capabilities map — provider-agnostic (Phase A2) ---------------
        Route::get('capabilities', [MessagingChannelController::class, 'capabilities'])
            ->name('messaging.capabilities');

        // --- Kết nối & quản lý kênh nhắn tin (UI /messaging/channels) ---
        Route::get('channels', [MessagingChannelController::class, 'index'])
            ->name('messaging.channels.index');
        Route::post('channels/{id}/sync', [MessagingChannelController::class, 'sync'])
            ->whereNumber('id')->name('messaging.channels.sync');     // messaging.connect
        Route::delete('channels/{id}', [MessagingChannelController::class, 'destroy'])
            ->whereNumber('id')->name('messaging.channels.destroy');

        // --- Web Push (thông báo tin nhắn mới khi tab đóng/ẩn) -------------
        Route::get('push/public-key', [PushSubscriptionController::class, 'publicKey'])->name('messaging.push.public_key');
        Route::post('push/subscribe', [PushSubscriptionController::class, 'subscribe'])->name('messaging.push.subscribe');
        Route::post('push/heartbeat', [PushSubscriptionController::class, 'heartbeat'])->name('messaging.push.heartbeat');
        Route::delete('push/subscribe', [PushSubscriptionController::class, 'unsubscribe'])->name('messaging.push.unsubscribe');

        // --- Tags (conversation labels) ------------------------------------
        Route::get('tags', [TagController::class, 'index'])->name('messaging.tags.index');                   // messaging.view
        Route::post('tags', [TagController::class, 'store'])->name('messaging.tags.store');                  // messaging.reply
        Route::patch('tags/{id}', [TagController::class, 'update'])->whereNumber('id')->name('messaging.tags.update');    // messaging.reply
        Route::delete('tags/{id}', [TagController::class, 'destroy'])->whereNumber('id')->name('messaging.tags.destroy'); // messaging.reply

        // --- Facebook comment moderation ------------------------------------
        Route::post('conversations/{id}/comment/hide', [FacebookCommentController::class, 'hide'])
            ->whereNumber('id')->name('messaging.comment.hide');
        Route::delete('conversations/{id}/comment', [FacebookCommentController::class, 'destroy'])
            ->whereNumber('id')->name('messaging.comment.destroy');
        Route::post('conversations/{id}/comment/reply', [FacebookCommentController::class, 'reply'])
            ->whereNumber('id')->name('messaging.comment.reply');
        Route::post('conversations/{id}/comment/private-reply', [FacebookCommentController::class, 'privateReply'])
            ->whereNumber('id')->name('messaging.comment.private_reply');

        // --- Auto-reply rules (S5) -----------------------------------------
        Route::get('auto-reply-rules', [AutoReplyRuleController::class, 'index'])
            ->name('messaging.rules.index');
        Route::post('auto-reply-rules', [AutoReplyRuleController::class, 'store'])
            ->name('messaging.rules.store');
        Route::patch('auto-reply-rules/{id}', [AutoReplyRuleController::class, 'update'])
            ->whereNumber('id')->name('messaging.rules.update');
        Route::delete('auto-reply-rules/{id}', [AutoReplyRuleController::class, 'destroy'])
            ->whereNumber('id')->name('messaging.rules.destroy');

        // --- Automation flows (kịch bản tự động, Flow Builder S3) -----------
        // RBAC dùng lại: đọc `messaging.view`, mutate `messaging.rule.manage`.
        Route::get('automation-flows', [AutomationFlowController::class, 'index'])
            ->name('messaging.flows.index');
        Route::post('automation-flows', [AutomationFlowController::class, 'store'])
            ->name('messaging.flows.store');
        Route::get('automation-flows/{id}', [AutomationFlowController::class, 'show'])
            ->whereNumber('id')->name('messaging.flows.show');
        Route::patch('automation-flows/{id}', [AutomationFlowController::class, 'update'])
            ->whereNumber('id')->name('messaging.flows.update');
        Route::delete('automation-flows/{id}', [AutomationFlowController::class, 'destroy'])
            ->whereNumber('id')->name('messaging.flows.destroy');
        Route::post('automation-flows/{id}/validate', [AutomationFlowController::class, 'validateGraph'])
            ->whereNumber('id')->name('messaging.flows.validate');
        Route::post('automation-flows/{id}/publish', [AutomationFlowController::class, 'publish'])
            ->whereNumber('id')->name('messaging.flows.publish');
        Route::post('automation-flows/{id}/pause', [AutomationFlowController::class, 'pause'])
            ->whereNumber('id')->name('messaging.flows.pause');
        Route::post('automation-flows/{id}/duplicate', [AutomationFlowController::class, 'duplicate'])
            ->whereNumber('id')->name('messaging.flows.duplicate');
    });

/*
 |--------------------------------------------------------------------------
 | Admin AI providers (S6) — super-admin, guard `admin_web`, KHÔNG tenant.
 |--------------------------------------------------------------------------
 | Cùng stack với module Admin/Settings (web + auth:admin_web).
 */
Route::middleware(['web', 'auth:admin_web', 'throttle:60,1'])
    ->prefix('api/v1/admin/ai-providers')->group(function () {
        Route::get('/', [AdminAiProviderController::class, 'index'])->name('admin.ai-providers.index');
        Route::post('/', [AdminAiProviderController::class, 'store'])->name('admin.ai-providers.store');
        Route::patch('{code}', [AdminAiProviderController::class, 'update'])
            ->where('code', '[a-z0-9][a-z0-9_-]*')->name('admin.ai-providers.update');
        Route::delete('{code}', [AdminAiProviderController::class, 'destroy'])
            ->where('code', '[a-z0-9][a-z0-9_-]*')->name('admin.ai-providers.destroy');
        Route::post('{code}/test', [AdminAiProviderController::class, 'test'])
            ->where('code', '[a-z0-9][a-z0-9_-]*')->name('admin.ai-providers.test');
    });
