<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Services\OrderFulfillmentService;
use App\Services\StockAllocationService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class SalesOrderController extends Controller
{
    public function __construct(protected StockAllocationService $allocationService, protected OrderFulfillmentService $fulfillmentService)
    {
    }

    // Create a sales order
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'shipping_notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity_ordered' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $subtotal += ($item['quantity_ordered'] * $item['unit_price']);
        }

        $order = SalesOrder::create([
            'customer_id' => $validated['customer_id'],
            'warehouse_id' => $validated['warehouse_id'],
            'created_by' => $request->user()->id,
            'order_number' => 'SO-' . now()->format('Ym') . '-' . strtoupper(Str::random(4)),
            'status' => 'pending',
            'subtotal' => $subtotal,
            'tax_amount' => 0.00,
            'shipping_fee' => 0.00,
            'total_amount' => $subtotal,
            'shipping_notes' => $validated['shipping_notes'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            $order->items()->create([
                'product_variant_id' => $item['product_variant_id'],
                'quantity_ordered' => $item['quantity_ordered'],
                'quantity_allocated' => 0,
                'unit_price' => $item['unit_price'],
                'subtotal' => $item['quantity_ordered'] * $item['unit_price'],
            ]);
        }

        return response()->json([
            'message' => 'Sales order created in pending status.',
            'data' => $order->load('items.variant'),
        ], 201);
    }

    public function allocateStock(SalesOrder $salesOrder): JsonResponse
    {
        try{
            $allocatedOrder = $this->allocationService->allocate($salesOrder);

            return response()->json([
                'message' => 'Inventory allocated successfully with FEFO/FIFO strategy.',
                'data' => $allocatedOrder,
            ]);
        }catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    // New method add
    public function dispatchOrder(Request $request, SalesOrder $salesOrder): JsonResponse
    {
        try{
            $dispatchOrder = $this->fulfillmentService->dispatchOrder($salesOrder, $request->user());

            return response()->json([
                'message' => 'Order dispatched successfully. Stock permanently deducted and invoice queued.',
                'data' => $dispatchOrder,
            ]);
        }catch(Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

}
