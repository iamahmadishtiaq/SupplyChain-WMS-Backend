<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_batch_id',
        'user_id',
        'type',
        'quantity_before',
        'quantity_adjusted',
        'quantity_after',
        'financial_impact',
        'reason',
    ];

    #[Override]
    protected function casts()
    {
        return [
            'quantity_before' => 'integer',
            'quantity_adjusted' => 'integer',
            'quantity_after' => 'integer',
            'financial_impact' => 'decimal:2',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'stock_batch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
