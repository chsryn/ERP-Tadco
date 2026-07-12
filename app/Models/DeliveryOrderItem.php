<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryOrderItem extends Model
{
    protected $fillable = [
        'delivery_order_id',
        'product_id',
        'tier_code',
        'qty',
        'unit_price',
        'discount_rate',
        'line_total',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_rate' => 'decimal:5',
        'line_total' => 'decimal:2',
    ];

    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
