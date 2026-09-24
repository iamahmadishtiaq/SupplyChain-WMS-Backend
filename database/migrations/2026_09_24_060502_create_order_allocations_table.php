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
        Schema::create('order_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_batch_id')->constrained()->restrictOnDelete();
            $table->integer('quantity_allocated');

            // Statuses: allocated, picked, cancelled
            $table->string('status', 30)->default('allocated');
            $table->timestamps();

            $table->index(['stock_batch_id', 'status'], 'idx_alloc_batch_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_allocations');
    }
};
