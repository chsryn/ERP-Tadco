<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryOrderItem extends Model
{
    protected $fillable = [
        'delivery_order_id',
        'product_id',
        'qty',
        'base_price',
        'discount_percentage',
        'final_price',
        'line_total',
    ];

    protected $casts = [
        'qty' => 'integer',
        'base_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'final_price' => 'decimal:2',
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
