<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PurchaseOrderController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


ROute::middleware('auth:sanctum')->group(function () {
    Route::post('purchase-orders', [PurchaseOrderController::class, 'store']);
    Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receiveGoods']);
});