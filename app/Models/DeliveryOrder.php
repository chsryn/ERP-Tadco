<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryOrder extends Model
{
    protected $fillable = [
        'do_number',
        'customer_id',
        'warehouse_id',
        'sales_id',
        'do_date',
        'planned_delivery_date',
        'total_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'do_date' => 'date',
        'planned_delivery_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function sales()
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    public function items()
    {
        return $this->hasMany(DeliveryOrderItem::class);
    }

    public function shipment()
    {
        return $this->hasOne(Shipment::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

}
