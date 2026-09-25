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
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_batch_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->string('type', 30);
            $table->integer('quantity_before');
            $table->integer('quantity_adjusted');
            $table->integer('quantity_after');
            $table->decimal('financial_impact', 14, 2);
            $table->text('reason');
            $table->timestamps();

            $table->index(['stock_batch_id', 'type'], 'idx_adj_batch_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
