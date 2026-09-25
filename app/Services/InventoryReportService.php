<?php

namespace App\Services;
use App\Models\ProductVariant;
use App\Models\StockAdjustment;
use App\Models\StockBatch;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class InventoryReportService
{
    public function getWarehouseValuation(?int $warehouseId = null): array
    {
        $query = DB::table('stock_batches')
           ->join('locations', 'stock_batches.location_id', '=', 'locations.id')
           ->join('zones', 'locations.zone_id', '=', 'zones.id')
           ->join('warehouses', 'zones.warehouse_id', '=', 'warehouses.id')
           ->select(
            'warehouses.id as warehouse_id',
            'warehouses.name as warehouse_name',
            'warehouses.code as warehouse_code',
            DB::raw('COUNT(DISTINCT stock_batches.id) as total_batches'),
            DB::raw('SUM(stock_batches.quantity_on_hand) as total_units_on_hand'),
            DB::raw('SUM(stock_batches.quantity_reserved) as total_units_reserved'),
            DB::raw('SUM((stock_batches.quantity_on_hand - stock_batches.quantity_reserved)) as total_units_available'),
            DB::raw('SUM(stock_batches.quantity_on_hand * stock_batches.unit_cost) as total_valuation'),
           )
           ->groupBy('warehouses.id', 'warehouses.name', 'warehouses.code');

           if ($warehouseId) {
            $query->where('warehouses.id', $warehouseId);
           }

           return $query->get()->toArray();
    }

    public function getAuditTrail(array $filters = []): array
    {
        $query = StockMovement::with(['batch.variant.product', 'user', 'sourceLocation', 'destinationLocation'])
           ->orderBy('id', 'desc');

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['stock_batch_id'])) {
            $query->where('stock_batch_id', $filters['stock_batch_id']);
        }

        return $query->paginate(20)->toArray();
    }

    public function getAdjustmentsSummary(): array
    {
        return [
            'total_write_offs_loss' => (float) StockAdjustment::where('quantity_adjusted', '<', 0)->sum('financial_impact'),
            'total_found_stock_gain' => (float) StockAdjustment::where('quantity_adjusted', '>'. 0)->sum('financial_impact'),
            'adjustments_breakdown' => StockAdjustment::with('batch.variant', 'user')
                ->latest()
                ->take(10)
                ->get()
                ->toArray(),
        ];
    }
}
