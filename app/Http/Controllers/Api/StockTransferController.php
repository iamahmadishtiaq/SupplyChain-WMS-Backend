<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\StockTransferService;
use Exception;
use Illuminate\Http\JsonResponse;

class StockTransferController extends Controller
{
    public function __construct(protected StockTransferService $transferService)
    {
    }

    public function transfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'stock_batch_id' => ['required', 'exists:stock_batches,id'],
            'destination_location_id' => ['required', 'exists:locations,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $result = $this->transferService->transfer(
                $validated['stock_batch_id'],
                $validated['destination_location_id'],
                $validated['quantity'],
                $request->user(),
                $validated['reason'] ?? null,
            );

            return response()->json([
                'message' => 'Stock transferred and movement ledger updated successfully.',
                'data' => $result,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
