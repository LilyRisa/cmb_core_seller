<?php

use CMBcoreSeller\Modules\Messaging\Http\Controllers\ConversationController;
use CMBcoreSeller\Modules\Messaging\Http\Controllers\MessageController;
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
        Route::patch('conversations/{id}', [ConversationController::class, 'update'])
            ->whereNumber('id')->name('messaging.conversations.update');

        // Send tin — rate limit chặt
        Route::middleware('throttle:30,1')->group(function () {
            Route::post('conversations/{id}/messages', [MessageController::class, 'sendText'])
                ->whereNumber('id')->name('messaging.messages.send');
            Route::post('conversations/{id}/messages/template', [MessageController::class, 'sendTemplate'])
                ->whereNumber('id')->name('messaging.messages.template');
            // POST conversations/{id}/messages/media     ⇒ S2 (FacebookConnector + MediaRelayService)
            // POST conversations/{id}/ai-suggestion       ⇒ S6
            // POST conversations/{id}/ai-suggestion/{id}/accept ⇒ S6
        });

        // CRUD endpoints (template/rule/knowledge/ai-config) đặt ở SPEC S3..S6.
    });
