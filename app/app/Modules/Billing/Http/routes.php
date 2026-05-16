<?php

use CMBcoreSeller\Modules\Billing\Http\Controllers\BillingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Billing routes — /api/v1/billing/* (Phase 6.4 / SPEC 0018).
|--------------------------------------------------------------------------
| Auth: Sanctum SPA cookie. Tenant: header X-Tenant-Id (qua middleware `tenant`).
| Permission kiểm trong controller: `billing.view` đọc, `billing.manage` ghi/checkout/cancel.
|
| Checkout cụ thể (SePay/VNPay) sẽ wire CheckoutSession thật ở PR2/PR3 — endpoint POST /checkout
| đã có nhưng trả placeholder cho cổng thanh toán ở PR1.
*/

Route::middleware(['api', 'auth:sanctum', 'verified', 'tenant'])->prefix('api/v1/billing')->group(function () {
    Route::get('plans', [BillingController::class, 'plans'])->name('billing.plans');
    Route::get('subscription', [BillingController::class, 'subscription'])->name('billing.subscription');
    Route::get('usage', [BillingController::class, 'usage'])->name('billing.usage');
    Route::post('checkout', [BillingController::class, 'checkout'])->middleware('throttle:10,1')->name('billing.checkout');
    // SPEC 0023 — user preview discount khi gõ mã ưu đãi ở /settings/plan.
    Route::post('vouchers/validate', [BillingController::class, 'validateVoucher'])
        ->middleware('throttle:30,1')->name('billing.vouchers.validate');
    Route::post('subscription/cancel', [BillingController::class, 'cancel'])->name('billing.subscription.cancel');

    Route::get('invoices', [BillingController::class, 'invoices'])->name('billing.invoices.index');
    Route::get('invoices/{id}', [BillingController::class, 'invoiceShow'])->whereNumber('id')->name('billing.invoices.show');
    Route::get('invoices/{id}/payment-status', [BillingController::class, 'invoicePaymentStatus'])->whereNumber('id')->name('billing.invoices.payment-status');

    Route::get('billing-profile', [BillingController::class, 'profileShow'])->name('billing.profile.show');
    Route::patch('billing-profile', [BillingController::class, 'profileUpdate'])->name('billing.profile.update');
});
