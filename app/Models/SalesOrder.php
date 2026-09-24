<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Override;

class SalesOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'warehouse_id',
        'created_by',
        'order_number',
        'status',
        'subtotal',
        'tax_amount',
        'shipping_fee',
        'total_amount',
        'shipping_notes',
    ];

    #[Override]
    public function casts()
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function customer() : BelongsTo 
    {
        return $this->belongsTo(Customer::class);   
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }
}
