<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Services\GoodsReceivingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Exception;

class PurchaseOrderController extends Controller
{
    public function __construct(protected GoodsReceivingService $receivingService)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouse,id'],
            'order_date' => ['required', 'date'],
            'expected_deleivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity_ordered' => ['required', 'integer', 'min:1'],
            'item.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $subtotal += ($item['quantity_ordered'] * $item['unit_cost']);
        }

        $po = PurchaseOrder::create([
            'supplier_id' => $validated['supplier_id'],
            'warehouse_id' => $validated['warehouse_id'],
            'created_by' => $request->user()->id,
            'po_number' => 'PO-' . now()->format('Ym') . '-' . strtoupper(Str::random(4)),
            'status' => 'draft',
            'order_date' => $validated['order_date'],
            'expected_deleivery_date' => $validated['expected_deleivery_date'] ?? null,
            'subtotal' => $subtotal,
            'tax_amount' => 0.00,
            'shipping_cost' => 0.00,
            'total_amount' => $subtotal,
            'notes' => $validated['notes'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            $po->items()->create([
                'product_variant_id' => $item['product_variant_id'],
                'quantity_ordered' => $item['quantity_ordered'],
                'quantity_received' => 0,
                'unit_cost' => $item['unit_cost'],
                'subtotal' => $item['quantity_ordered'] * $item['unit_cost'],
            ]);
        }
        
        return response()->json([
            'message' => 'Purchase order created successfully',
            'data' => $po->load('items.variant'),
        ], 201);
    }

    // Receive goods against a purchase order.
    public function receiveGoods(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:purchase_order_items,id'],
            'items.*.location_id' => ['required', 'exists:locations,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.manufactured_at' => ['nullable', 'date'],
        ]);

        try {
            $updatedPo = $this->receivingService->receive(
                $purchaseOrder,
                $validated['items'],
                $request->user()
            );

            return response()->json([
                'message' => 'Goods received, stock batches initialized, and movement ledger updated.',
                'data' => $updatedPo,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
