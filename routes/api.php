<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\SalesOrderController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StockTransferController;
use App\Http\Controllers\Api\StockAdjustmentController;
use App\Http\Controllers\Api\ReportController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public Route
Route::post('login', [AuthController::class, 'login']);

ROute::middleware('auth:sanctum')->group(function () {

    Route::middleware('permission:create-purchase-order')->group(function () {
        Route::post('purchase-orders', [PurchaseOrderController::class, 'store']);
    });

    Route::middleware('permission:receive-goods')->group(function () {
        Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receiveGoods']);
    });

    Route::middleware('permission:create-sales-order')->group(function () {
        Route::post('sales-orders', [SalesOrderController::class, 'store']);
    });

    Route::middleware('permission:allocate-stock')->group(function () {
        Route::post('sales-orders/{salesOrder}/allocate', [SalesOrderController::class, 'allocateStock']);
    });
    Route::middleware('permission:dispatch-order')->group(function () {
        Route::post('sales-orders/{salesOrder}/dispatch', [SalesOrderController::class, 'dispatchOrder']);
    });
    Route::middleware('permission:transfer-stock')->group(function () {
        Route::post('inventory/transfer', [StockTransferController::class, 'transfer']);
    });

    Route::middleware('permission:adjust-stock')->group(function () {
        Route::post('inventory/adjust', [StockAdjustmentController::class, 'store']);
    });

    Route::middleware('permission:view-reports')->prefix('reports')->group(function () {
        Route::get('valuation', [ReportController::class, 'warehouseValuation']);
        Route::get('audit-trail', [ReportController::class, 'auditTrail']);
        Route::get('adjustments-summary', [ReportController::class, 'adjustmentsSummary']);
    });

    Route::post('logout', [AuthController::class, 'logout']);
});
