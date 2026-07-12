<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = [
        'warehouse_code',
        'warehouse_name',
        'address',
        'is_active',
    ];

    public function stockBalances()
    {
        return $this->hasMany(StockBalance::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
