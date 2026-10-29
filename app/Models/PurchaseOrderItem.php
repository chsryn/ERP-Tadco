<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'dynamics_code',
        'weight_kg',
        'qty_plan',
        'qty_ton',
        'week_1',
        'week_2',
        'week_3',
        'week_4',
        'price',
        'special_discount',
        'price_nett',
        'total_price',
        'qty_received',
    ];

    protected $casts = [
        'weight_kg' => 'decimal:2',
        'qty_plan' => 'decimal:2',
        'qty_ton' => 'decimal:4',
        'week_1' => 'decimal:2',
        'week_2' => 'decimal:2',
        'week_3' => 'decimal:2',
        'week_4' => 'decimal:2',
        'price' => 'decimal:2',
        'special_discount' => 'decimal:2',
        'price_nett' => 'decimal:2',
        'total_price' => 'decimal:2',
        'qty_received' => 'decimal:2',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
