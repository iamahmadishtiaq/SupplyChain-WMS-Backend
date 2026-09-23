<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->string('po_number')->unique();

            // Statuses: draft, ordered, partially_received, received, cancelled
            $table->string('status', 30)->default('draft');

            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();

            $table->decimal('subtotal', 14, 2)->default(0.00);
            $table->decimal('tax_amount', 14, 2)->default(0.00);
            $table->decimal('shupping_cost', 14, 2)->default(0.00);
            $table->decimal('total_amount', 14, 2)->default(0.00);

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id', 'status'], 'idx_po_wh_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
