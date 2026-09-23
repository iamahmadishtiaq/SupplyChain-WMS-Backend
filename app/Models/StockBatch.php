<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'location_id',
        'batch_number',
        'quantity_received',
        'quantity_on_hand',
        'quantity_reserved',
        'unit_cost',
        'manufactured_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_received' => 'integer',
            'quantity_on_hand' => 'integer',
            'quantity_reserved' => 'integer',
            'unit_cost' => 'decimal:2',
            'manufactured_at' => 'date',
            'expires_at' => 'date',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}