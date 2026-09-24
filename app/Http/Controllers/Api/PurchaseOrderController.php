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
    public function __construct(protected GoodsReceivingService $receivingService) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'order_date' => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity_ordered' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Calculate subtotal safely
        $subtotal = 0;
        $processedItems = [];

        foreach ($validated['items'] as $item) {
            $variant = \App\Models\ProductVariant::find($item['product_variant_id']);
            $cost = isset($item['unit_cost']) ? (float) $item['unit_cost'] : (float) ($variant->cost_price ?? 0);
            $qty = (int) $item['quantity_ordered'];

            $itemSubtotal = $qty * $cost;
            $subtotal += $itemSubtotal;

            $processedItems[] = [
                'product_variant_id' => $variant->id,
                'quantity_ordered' => $qty,
                'quantity_received' => 0,
                'unit_cost' => $cost,
                'subtotal' => $itemSubtotal,
            ];
        }

        $po = PurchaseOrder::create([
            'supplier_id' => $validated['supplier_id'],
            'warehouse_id' => $validated['warehouse_id'],
            'created_by' => $request->user()->id,
            'po_number' => 'PO-' . now()->format('Ym') . '-' . strtoupper(\Illuminate\Support\Str::random(4)),
            'status' => 'draft',
            'order_date' => $validated['order_date'],
            'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
            'subtotal' => $subtotal,
            'tax_amount' => 0.00,
            'total_amount' => $subtotal,
            'notes' => $validated['notes'] ?? null,
        ]);

        foreach ($processedItems as $itemData) {
            $po->items()->create($itemData);
        }

        return response()->json([
            'message' => 'Purchase order created successfully.',
            'data' => $po->load(['items.variant', 'supplier', 'warehouse']),
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
