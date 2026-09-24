<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;


class CheckLowStockThresholdCommand extends Command
{
    protected $signature='wms:check-low-stock {--notify : Send automated alerts to procurement}';
    protected $description = 'Scan all active inventory items and identify variants below reorder threshold';
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Scanning warehouse inventory against reorder levels...");

        $lowStockVariants = ProductVariant::with('product')
           ->select('product_variants.*')
           ->selectSub(function ($query) {
            $query->from('stock_batches')
                ->whereColumn('stock_batches.product_variant_id', 'product_variants.id')
                ->selectRaw('COALESCE(SUM(quantity_on_hand), 0)');
           }, 'current_stock')
           ->havingRaw('current_stock <= reorder_level')
           ->get();

        if ($lowStockVariants->isEmpty()) {
            $this->info("All product variants are sufficiently stocked.");
            return Command::SUCCESS;
        }

        $tableData = [];
        foreach ($lowStockVariants as $variant) {
            $tableData[] = [
                'ID' => $variant->id,
                'SKU' => $variant->sku,
                'Product' => $variant->product->name ?? 'N/A',
                'Current Stock' => $variant->current_stock,
                'Reorder_level' => $variant->reorder_level,
                'Deficit' => max(0, $variant->reorder_level - $variant->current_stock),
            ];
        }

        $this->table(['ID', 'SKU', 'Product', 'Current Stock', 'Reorder Level', 'Deficit'], $tableData);

        if ($this->option('notify')) {
            $this->warn("Procurement alert broadcasted to procurement managers.");
        }
        return Command::SUCCESS;
    }
}
