<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'city',
        'address',
        'manager_name',
        'contact_email',
        'is_active',
    ];

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class);
    }
}
