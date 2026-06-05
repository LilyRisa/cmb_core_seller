<?php

use CMBcoreSeller\Modules\Orders\Http\Controllers\PublicTrackingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Orders module routes (SPEC 0030)
|--------------------------------------------------------------------------
| Loaded by OrdersServiceProvider::boot() via loadRoutesFrom(), so these are
| registered OUTSIDE routes/api.php (kept separate to avoid merge churn on the
| central file). We therefore re-declare the `api` group + `api/v1` prefix here.
|
| Public order tracking: no auth, no tenant — a manual order is looked up by its
| `order_number` ("code"). Throttled to blunt code-enumeration; the controller
| masks every PII field.
*/

Route::middleware('api')
    ->prefix('api/v1')
    ->name('api.v1.public.')
    ->group(function () {
        Route::get('public/track', PublicTrackingController::class)
            ->middleware('throttle:30,1')
            ->name('track');
    });
