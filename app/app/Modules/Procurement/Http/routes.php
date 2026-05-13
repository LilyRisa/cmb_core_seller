<?php

use CMBcoreSeller\Modules\Procurement\Http\Controllers\DemandPlanningController;
use CMBcoreSeller\Modules\Procurement\Http\Controllers\PurchaseOrderController;
use CMBcoreSeller\Modules\Procurement\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

/*
 |--------------------------------------------------------------------------
 | Procurement routes — /api/v1/* (Phase 6.1 — SPEC 0014).
 |--------------------------------------------------------------------------
 | Auth + tenant: chung middleware với toàn bộ /api/v1 (xem app/routes/api.php).
 | Permissions kiểm trong controller: `procurement.view` (đọc), `procurement.manage` (sửa header
 | + giá), `procurement.receive` (tạo phiếu nhập từ PO — kho).
 */

Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/v1')->group(function () {
    // Nhà cung cấp
    Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::get('suppliers/{id}', [SupplierController::class, 'show'])->whereNumber('id')->name('suppliers.show');
    Route::patch('suppliers/{id}', [SupplierController::class, 'update'])->whereNumber('id')->name('suppliers.update');
    Route::delete('suppliers/{id}', [SupplierController::class, 'destroy'])->whereNumber('id')->name('suppliers.destroy');
    // Bảng giá nhập per NCC
    Route::post('suppliers/{id}/prices', [SupplierController::class, 'setPrice'])->whereNumber('id')->name('suppliers.prices.set');
    Route::delete('suppliers/{id}/prices/{priceId}', [SupplierController::class, 'deletePrice'])->whereNumber('id')->whereNumber('priceId')->name('suppliers.prices.delete');

    // Đơn mua (PO)
    Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
    Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
    Route::get('purchase-orders/{id}', [PurchaseOrderController::class, 'show'])->whereNumber('id')->name('purchase-orders.show');
    Route::patch('purchase-orders/{id}', [PurchaseOrderController::class, 'update'])->whereNumber('id')->name('purchase-orders.update');
    Route::post('purchase-orders/{id}/confirm', [PurchaseOrderController::class, 'confirm'])->whereNumber('id')->name('purchase-orders.confirm');
    Route::post('purchase-orders/{id}/cancel', [PurchaseOrderController::class, 'cancel'])->whereNumber('id')->name('purchase-orders.cancel');
    Route::post('purchase-orders/{id}/receive', [PurchaseOrderController::class, 'receive'])->whereNumber('id')->name('purchase-orders.receive');

    // Đề xuất nhập hàng (Phase 6.3)
    Route::get('procurement/demand-planning', [DemandPlanningController::class, 'index'])->name('procurement.demand-planning');
    Route::post('procurement/demand-planning/create-po', [DemandPlanningController::class, 'createPo'])->name('procurement.demand-planning.create-po');
});
