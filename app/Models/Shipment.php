<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $fillable = [
        'shipment_number',
        'delivery_order_id',
        'shipment_date',
        'received_date',
        'driver_name',
        'vehicle_no',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'shipment_date' => 'date',
        'received_date' => 'date',
    ];

    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
