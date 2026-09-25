<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\StockAdjustmentService;
use Exception;
use Illuminate\Http\JsonResponse;

class StockAdjustmentController extends Controller
{
    public function __construct(protected StockAdjustmentService $adjustmentService)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'stock_batch_id' => ['required', 'exists:stock_batches,id'],
            'type' => ['required', 'string', 'in:write_off,discrepancy_loss,found_stock'],
            'quantity_adjusted' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $adjustment = $this->adjustmentService->adjustStock(
                $validated['stock_batch_id'],
                $validated['type'],
                $validated['quantity_adjusted'],
                $validated['reason'],
                $request->user()
            );

            return response()->json([
                'message' => 'Stock adjusted and ledger movement posted successfully.',
                'data' => $adjustment,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
