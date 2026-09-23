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
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->string('batch_number');
            $table->integer('quantity_received');
            $table->integer('quantity_on_hand');
            $table->integer('quantity_reserved')->default(0);
            $table->decimal('unit_cost', 12, 2);
            $table->date('manufactured_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamps();

            $table->index(['product_variant_id', 'quantity_on_hand', 'expires_at'], 'idx_batches_fifo_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};
