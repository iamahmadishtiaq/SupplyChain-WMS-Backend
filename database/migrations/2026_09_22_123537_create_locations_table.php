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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained()->cascadeOnDelete();
            $table->string('aisle', 10);
            $table->string('rack', 10);
            $table->string('shelf', 10);
            $table->string('bin', 10);
            $table->string('barcode')->unique();
            $table->decimal('max_weight_capacity_kg', 8, 2)->default(500.00);
            $table->boolean('is_occupied')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
