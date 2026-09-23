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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Movement Type: inbound, outbound, transfer
            $table->string('type', 30);

            // Quantity change: +100 (inbound) or -20 (outbound)
            $table->integer('quantity_change');
            $table->integer('quantity_before');
            $table->integer('quantity_after');

            // Location Tracking (if transfer)
            $table->foreignId('source_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('destination_location_id')->nullable()->constrained('locations')->nullOnDelete();

            // Polymorphic Columns: reference_type & reference_id
            // (e.g: App\Models\PurchaseOrder, App\Models\SalesOrder)
            $table->nullableMorphs('reference');

            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['stock_batch_id', 'type'], 'idx_batch_movement_type');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
