<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'delivery_order_id',
        'customer_id',
        'invoice_date',
        'payment_term_days',
        'due_date',
        'subtotal',
        'discount_total',
        'grand_total',
        'paid_total',
        'receivable_amount',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_total' => 'decimal:2',
        'receivable_amount' => 'decimal:2',
    ];

    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
