<?php

use CMBcoreSeller\Modules\Support\Http\Controllers\AdminAiSupportController;
use CMBcoreSeller\Modules\Support\Http\Controllers\AdminSupportConversationController;
use CMBcoreSeller\Modules\Support\Http\Controllers\HelpAssistantController;
use CMBcoreSeller\Modules\Support\Http\Controllers\SupportConversationController;
use Illuminate\Support\Facades\Route;

/*
 |--------------------------------------------------------------------------
 | Support (trợ lý trợ giúp + CSKH) — /api/v1/support/*
 |--------------------------------------------------------------------------
 |
 | KHÔNG gate theo plan.feature — trợ giúp dùng được cho MỌI gói. Vẫn cần đăng
 | nhập + tenant để gắn cost AI / lưu hội thoại CSKH theo tenant. Throttle nhẹ.
 */

Route::middleware(['api', 'auth:sanctum', 'verified', 'tenant'])
    ->prefix('api/v1/support')->group(function () {
        // Tab "Hỏi AI" — RAG hỏi-đáp cách dùng hệ thống.
        Route::post('assistant/ask', [HelpAssistantController::class, 'ask'])
            ->middleware('throttle:30,1')->name('support.assistant.ask');

        // Tab "Hỏi CSKH" — hội thoại nhiều tin + đính kèm (SPEC-0028).
        Route::get('conversations', [SupportConversationController::class, 'index'])->name('support.conversations.index');
        Route::get('unread', [SupportConversationController::class, 'unread'])->name('support.unread');
        Route::post('messages', [SupportConversationController::class, 'store'])
            ->middleware('throttle:20,1')->name('support.messages.store');
        Route::post('conversations/{id}/read', [SupportConversationController::class, 'read'])
            ->whereNumber('id')->name('support.conversations.read');
    });

// --- Admin: xem & trả lời hội thoại CSKH XUYÊN tenant (guard admin_web) -------
Route::middleware(['web', 'auth:admin_web', 'throttle:60,1'])
    ->prefix('api/v1/admin/support-conversations')->group(function () {
        Route::get('/', [AdminSupportConversationController::class, 'index'])->name('admin.support-conversations.index');
        Route::get('{id}', [AdminSupportConversationController::class, 'show'])
            ->whereNumber('id')->name('admin.support-conversations.show');
        Route::post('{id}/messages', [AdminSupportConversationController::class, 'message'])
            ->whereNumber('id')->name('admin.support-conversations.message');
        Route::post('{id}/close', [AdminSupportConversationController::class, 'close'])
            ->whereNumber('id')->name('admin.support-conversations.close');
    });

// --- Admin: test kết nối "nháp" cho form AI Trợ giúp (gate nút Lưu, SPEC §5.4) --------
Route::middleware(['web', 'auth:admin_web', 'throttle:60,1'])
    ->prefix('api/v1/admin/ai-support')->group(function () {
        Route::post('test-draft', [AdminAiSupportController::class, 'testDraft'])
            ->name('admin.ai-support.test-draft');
    });
