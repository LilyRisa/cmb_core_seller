<?php

use CMBcoreSeller\Modules\Support\Http\Controllers\HelpAssistantController;
use CMBcoreSeller\Modules\Support\Http\Controllers\SupportRequestController;
use Illuminate\Support\Facades\Route;

/*
 |--------------------------------------------------------------------------
 | Support (trợ lý trợ giúp + CSKH) — /api/v1/support/*
 |--------------------------------------------------------------------------
 |
 | KHÔNG gate theo plan.feature — trợ giúp dùng được cho MỌI gói. Vẫn cần đăng
 | nhập + tenant để gắn cost AI / lưu yêu cầu CSKH theo tenant. Throttle nhẹ.
 */

Route::middleware(['api', 'auth:sanctum', 'verified', 'tenant'])
    ->prefix('api/v1/support')->group(function () {
        // Tab "Hỏi AI" — RAG hỏi-đáp cách dùng hệ thống.
        Route::post('assistant/ask', [HelpAssistantController::class, 'ask'])
            ->middleware('throttle:30,1')->name('support.assistant.ask');

        // Tab "Hỏi CSKH" — gửi câu hỏi vào hàng đợi chờ phản hồi.
        Route::get('requests', [SupportRequestController::class, 'index'])->name('support.requests.index');
        Route::post('requests', [SupportRequestController::class, 'store'])
            ->middleware('throttle:10,1')->name('support.requests.store');
    });
