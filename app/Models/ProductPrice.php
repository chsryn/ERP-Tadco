<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPrice extends Model
{
    protected $fillable = [
        'product_id',
        'tier_code',
        'price',
        'discount_rate',
        'valid_from',
        'valid_until',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
