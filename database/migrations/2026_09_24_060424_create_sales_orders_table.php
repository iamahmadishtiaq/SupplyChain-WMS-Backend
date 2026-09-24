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
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->string('order_number')->unique();

            // Statuses: pending, allocated, picking, packed, dispatched, cancelled
            $table->string('status', 30)->default('pending');

            $table->decimal('subtotal', 14, 2)->default(0.00);
            $table->decimal('tax_amount', 14, 2)->default(0.00);
            $table->decimal('shipping_fee', 12, 2)->default(0.00);
            $table->decimal('total_amount', 12, 2)->default(0.00);

            $table->text('shipping_notes')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id', 'status'], 'idx_so_wh_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
