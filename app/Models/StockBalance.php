<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockBalance extends Model
{
    protected $fillable = [
        'warehouse_id',
        'product_id',
        'qty_available',
        'qty_reserved',
    ];

    protected $casts = [
        'qty_available' => 'decimal:4',
        'qty_reserved' => 'decimal:4',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
