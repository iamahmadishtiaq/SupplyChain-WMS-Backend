<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\SalesOrderController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StockTransferController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public Route
Route::post('login', [AuthController::class, 'login']);

ROute::middleware('auth:sanctum')->group(function () {
    Route::post('purchase-orders', [PurchaseOrderController::class, 'store']);
    Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receiveGoods']);

    Route::post('sales-orders', [SalesOrderController::class, 'store']);
    Route::post('sales-orders/{salesOrder}/allocate', [SalesOrderController::class, 'allocateStock']);
    Route::post('sales-orders/{salesOrder}/dispatch', [SalesOrderController::class, 'dispatchOrder']);

    Route::post('inventory/transfer', [StockTransferController::class, 'transfer']);

    Route::post('logout', [AuthController::class, 'logout']);
});