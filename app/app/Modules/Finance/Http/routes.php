<?php

use CMBcoreSeller\Modules\Finance\Http\Controllers\SettlementController;
use Illuminate\Support\Facades\Route;

/*
 |--------------------------------------------------------------------------
 | Finance routes — /api/v1/* (Phase 6.2 / SPEC 0016).
 |--------------------------------------------------------------------------
 | Permissions:
 |   - `finance.view`: index/show.
 |   - `finance.reconcile`: manual reconcile + trigger fetch.
 */

// SPEC 0018 — gating tính năng `finance_settlements` (chỉ gói Pro/Business). Plan thấp ⇒ `402 PLAN_FEATURE_LOCKED`.
Route::middleware(['api', 'auth:sanctum', 'verified', 'tenant', 'plan.feature:finance_settlements'])->prefix('api/v1')->group(function () {
    Route::get('settlements', [SettlementController::class, 'index'])->name('settlements.index');
    Route::get('settlements/{id}', [SettlementController::class, 'show'])->whereNumber('id')->name('settlements.show');
    Route::post('settlements/{id}/reconcile', [SettlementController::class, 'reconcile'])->whereNumber('id')->name('settlements.reconcile');
    Route::post('channel-accounts/{id}/fetch-settlements', [SettlementController::class, 'fetchForShop'])->whereNumber('id')->name('channel-accounts.fetch-settlements');
});
