<?php

use CMBcoreSeller\Modules\Accounting\Http\Controllers\ApController;
use CMBcoreSeller\Modules\Accounting\Http\Controllers\ArController;
use CMBcoreSeller\Modules\Accounting\Http\Controllers\BalanceController;
use CMBcoreSeller\Modules\Accounting\Http\Controllers\CashController;
use CMBcoreSeller\Modules\Accounting\Http\Controllers\ChartAccountController;
use CMBcoreSeller\Modules\Accounting\Http\Controllers\FiscalPeriodController;
use CMBcoreSeller\Modules\Accounting\Http\Controllers\JournalController;
use CMBcoreSeller\Modules\Accounting\Http\Controllers\PostRuleController;
use CMBcoreSeller\Modules\Accounting\Http\Controllers\ReportController;
use CMBcoreSeller\Modules\Accounting\Http\Controllers\SetupController;
use Illuminate\Support\Facades\Route;

/*
 |--------------------------------------------------------------------------
 | Accounting routes — /api/v1/accounting/* (Phase 7.1 / SPEC 0019).
 |--------------------------------------------------------------------------
 | Permissions (xem app/Modules/Tenancy/Enums/Role.php):
 |   - `accounting.view`: index/show/balances.
 |   - `accounting.post`: tạo bút toán tay + reverse.
 |   - `accounting.close_period`: đóng/mở/khoá kỳ.
 |   - `accounting.config`: setup + sửa CoA + sửa post rules.
 |
 | Plan gating: middleware `plan.feature:accounting_basic` (Pro/Business). Plan thấp ⇒ 402.
 |
 | Lưu ý: permission check làm ở controller qua `Gate` (đã có `Gate::before` set ở Tenancy).
 */
Route::middleware(['api', 'auth:sanctum', 'tenant', 'plan.feature:accounting_basic'])
    ->prefix('api/v1/accounting')->group(function () {
        // Setup (idempotent).
        Route::get('setup/status', [SetupController::class, 'status'])->name('accounting.setup.status');
        Route::post('setup', [SetupController::class, 'run'])
            ->middleware('can:accounting.config')->name('accounting.setup.run');

        // Chart of Accounts.
        Route::get('accounts', [ChartAccountController::class, 'index'])
            ->middleware('can:accounting.view')->name('accounting.accounts.index');
        Route::post('accounts', [ChartAccountController::class, 'store'])
            ->middleware('can:accounting.config')->name('accounting.accounts.store');
        Route::patch('accounts/{id}', [ChartAccountController::class, 'update'])
            ->whereNumber('id')->middleware('can:accounting.config')->name('accounting.accounts.update');
        Route::delete('accounts/{id}', [ChartAccountController::class, 'destroy'])
            ->whereNumber('id')->middleware('can:accounting.config')->name('accounting.accounts.destroy');

        // Fiscal periods.
        Route::get('periods', [FiscalPeriodController::class, 'index'])
            ->middleware('can:accounting.view')->name('accounting.periods.index');
        Route::post('periods/ensure-year', [FiscalPeriodController::class, 'ensureYear'])
            ->middleware('can:accounting.config')->name('accounting.periods.ensure-year');
        Route::post('periods/{code}/close', [FiscalPeriodController::class, 'close'])
            ->middleware('can:accounting.close_period')->name('accounting.periods.close');
        Route::post('periods/{code}/reopen', [FiscalPeriodController::class, 'reopen'])
            ->middleware('can:accounting.close_period')->name('accounting.periods.reopen');
        Route::post('periods/{code}/lock', [FiscalPeriodController::class, 'lock'])
            ->middleware('can:accounting.close_period')->name('accounting.periods.lock');

        // Journal entries.
        Route::get('journals', [JournalController::class, 'index'])
            ->middleware('can:accounting.view')->name('accounting.journals.index');
        Route::post('journals', [JournalController::class, 'store'])
            ->middleware(['can:accounting.post', 'throttle:30,1'])->name('accounting.journals.store');
        Route::get('journals/{id}', [JournalController::class, 'show'])
            ->whereNumber('id')->middleware('can:accounting.view')->name('accounting.journals.show');
        Route::post('journals/{id}/reverse', [JournalController::class, 'reverse'])
            ->whereNumber('id')->middleware('can:accounting.post')->name('accounting.journals.reverse');

        // Balances.
        Route::get('balances', [BalanceController::class, 'index'])
            ->middleware('can:accounting.view')->name('accounting.balances.index');
        Route::post('balances/recompute', [BalanceController::class, 'recompute'])
            ->middleware('can:accounting.config')->name('accounting.balances.recompute');

        // Post rules (mapping CoA cho auto-post).
        Route::get('post-rules', [PostRuleController::class, 'index'])
            ->middleware('can:accounting.view')->name('accounting.post-rules.index');
        Route::patch('post-rules/{eventKey}', [PostRuleController::class, 'update'])
            ->middleware('can:accounting.config')->name('accounting.post-rules.update');

        // AR — Phase 7.2.
        Route::get('ar/aging', [ArController::class, 'aging'])
            ->middleware('can:accounting.view')->name('accounting.ar.aging');
        Route::get('ar/customers/{customerId}/balance', [ArController::class, 'balance'])
            ->whereNumber('customerId')->middleware('can:accounting.view')->name('accounting.ar.balance');
        Route::get('customer-receipts', [ArController::class, 'listReceipts'])
            ->middleware('can:accounting.view')->name('accounting.receipts.index');
        Route::post('customer-receipts', [ArController::class, 'createReceipt'])
            ->middleware('can:accounting.post')->name('accounting.receipts.store');
        Route::post('customer-receipts/{id}/confirm', [ArController::class, 'confirmReceipt'])
            ->whereNumber('id')->middleware('can:accounting.post')->name('accounting.receipts.confirm');
        Route::post('customer-receipts/{id}/cancel', [ArController::class, 'cancelReceipt'])
            ->whereNumber('id')->middleware('can:accounting.post')->name('accounting.receipts.cancel');

        // AP — Phase 7.3.
        Route::get('ap/aging', [ApController::class, 'aging'])
            ->middleware('can:accounting.view')->name('accounting.ap.aging');
        Route::get('vendor-bills', [ApController::class, 'listBills'])
            ->middleware('can:accounting.view')->name('accounting.bills.index');
        Route::post('vendor-bills', [ApController::class, 'createBill'])
            ->middleware('can:accounting.post')->name('accounting.bills.store');
        Route::post('vendor-bills/{id}/record', [ApController::class, 'recordBill'])
            ->whereNumber('id')->middleware('can:accounting.post')->name('accounting.bills.record');
        Route::get('vendor-payments', [ApController::class, 'listPayments'])
            ->middleware('can:accounting.view')->name('accounting.payments.index');
        Route::post('vendor-payments', [ApController::class, 'createPayment'])
            ->middleware('can:accounting.post')->name('accounting.payments.store');
        Route::post('vendor-payments/{id}/confirm', [ApController::class, 'confirmPayment'])
            ->whereNumber('id')->middleware('can:accounting.post')->name('accounting.payments.confirm');

        // Phase 7.4 — Cash & Bank (gated `accounting_advanced` cho bank-reconcile; cash_accounts dùng được ở basic).
        Route::get('cash-accounts', [CashController::class, 'index'])
            ->middleware('can:accounting.view')->name('accounting.cash-accounts.index');
        Route::post('cash-accounts', [CashController::class, 'store'])
            ->middleware('can:accounting.config')->name('accounting.cash-accounts.store');
        Route::middleware('plan.feature:accounting_advanced')->group(function () {
            Route::get('bank-statements', [CashController::class, 'listStatements'])
                ->middleware('can:accounting.view')->name('accounting.bank-statements.index');
            Route::get('bank-statements/{id}', [CashController::class, 'showStatement'])
                ->whereNumber('id')->middleware('can:accounting.view')->name('accounting.bank-statements.show');
            Route::post('bank-statements/import', [CashController::class, 'importStatement'])
                ->middleware('can:accounting.post')->name('accounting.bank-statements.import');
            Route::post('bank-statement-lines/{id}/match', [CashController::class, 'matchLine'])
                ->whereNumber('id')->middleware('can:accounting.post')->name('accounting.bank-statement-lines.match');
            Route::post('bank-statement-lines/{id}/ignore', [CashController::class, 'ignoreLine'])
                ->whereNumber('id')->middleware('can:accounting.post')->name('accounting.bank-statement-lines.ignore');
        });

        // Phase 7.5 — Báo cáo tài chính (accounting_basic) + VAT + Export MISA (accounting_advanced).
        Route::get('reports/trial-balance', [ReportController::class, 'trialBalance'])
            ->middleware('can:accounting.view')->name('accounting.reports.trial-balance');
        Route::get('reports/profit-loss', [ReportController::class, 'profitLoss'])
            ->middleware('can:accounting.view')->name('accounting.reports.profit-loss');
        Route::get('reports/balance-sheet', [ReportController::class, 'balanceSheet'])
            ->middleware('can:accounting.view')->name('accounting.reports.balance-sheet');
        Route::get('reports/ledger', [ReportController::class, 'ledger'])
            ->middleware('can:accounting.view')->name('accounting.reports.ledger');
        Route::middleware('plan.feature:accounting_advanced')->group(function () {
            Route::get('reports/vat', [ReportController::class, 'vat'])
                ->middleware('can:accounting.view')->name('accounting.reports.vat');
            Route::post('tax-filings', [ReportController::class, 'createFiling'])
                ->middleware('can:accounting.post')->name('accounting.tax-filings.create');
            Route::get('reports/export-misa', [ReportController::class, 'exportMisa'])
                ->middleware('can:accounting.export')->name('accounting.reports.export-misa');
        });
    });
