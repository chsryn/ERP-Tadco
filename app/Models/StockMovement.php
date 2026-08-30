<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'warehouse_id',
        'product_id',
        'movement_date',
        'movement_type',
        'qty',
        'source_type',
        'source_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'movement_date' => 'datetime',
        'qty' => 'decimal:4',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
