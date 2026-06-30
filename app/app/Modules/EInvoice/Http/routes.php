<?php

use CMBcoreSeller\Modules\EInvoice\Http\Controllers\EInvoiceAccountController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum', 'verified', 'tenant', 'plan.feature:einvoice'])
    ->prefix('api/v1/einvoice')->group(function () {
        Route::get('accounts', [EInvoiceAccountController::class, 'index'])->name('einvoice.accounts.index');
        Route::post('accounts', [EInvoiceAccountController::class, 'store'])->name('einvoice.accounts.store');
        Route::patch('accounts/{id}', [EInvoiceAccountController::class, 'update'])->whereNumber('id')->name('einvoice.accounts.update');
        Route::delete('accounts/{id}', [EInvoiceAccountController::class, 'destroy'])->whereNumber('id')->name('einvoice.accounts.destroy');
        Route::post('accounts/{id}/verify', [EInvoiceAccountController::class, 'verify'])->whereNumber('id')->name('einvoice.accounts.verify');
        Route::get('accounts/{id}/company-info', [EInvoiceAccountController::class, 'companyInfo'])->whereNumber('id')->name('einvoice.accounts.company-info');
        Route::get('accounts/{id}/templates', [EInvoiceAccountController::class, 'templates'])->whereNumber('id')->name('einvoice.accounts.templates');
    });
