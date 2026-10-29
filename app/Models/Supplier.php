<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'code',
        'name',
        'region',
        'port_depo',
        'delivery_mode',
        'term_of_payment_days',
        'pic_name',
        'is_active',
    ];

    protected $casts = [
        'term_of_payment_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
