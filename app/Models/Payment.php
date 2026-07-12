<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'payment_number',
        'invoice_id',
        'payment_date',
        'payment_method',
        'amount',
        'bank_name',
        'reference_no',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
