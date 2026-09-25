<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\InventoryReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(protected InventoryReportService $reportService)
    {
    }

    public function warehouseValuation(Request $request): JsonResponse
    {
        $warehouseId = $request->query('warehouse_id');
        $data = $this->reportService->getWarehouseValuation($warehouseId ? (int) $warehouseId : null);

        return response()->json([
            'message' => 'Warehouse inventory valuation retrieved successfully.',
            'data' => $data,
        ]);
    }

    public function auditTrail(Request $request): JsonResponse
    {
        $filters = $request->only(['type', 'stock_batch_id']);
        $data = $this->reportService->getAuditTrail($filters);

        return response()->json([
            'message' => 'Stock movements audit trail retrieved successfully.',
            'data' => $data,
        ]);
    }

    public function adjustmentsSummary(): JsonResponse
    {
        $data = $this->reportService->getAdjustmentsSummary();

        return response()->json([
            'message' => 'Stock adjustments financial summary retrieved successfully.',
            'data' => $data,
        ]);
    }
}
