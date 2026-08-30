<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductDiscount extends Model
{
    protected $fillable = [
        'product_id',
        'strata_level',
        'min_qty',
        'max_qty',
        'discount_percentage',
    ];

    protected $casts = [
        'min_qty' => 'decimal:4',
        'max_qty' => 'decimal:4',
        'discount_percentage' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
