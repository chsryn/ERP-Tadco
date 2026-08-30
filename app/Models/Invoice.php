<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected static function booted()
    {
        // Event CREATING: Generate nomor faktur sebelum data baru masuk ke database
        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $lastInvoice = self::orderBy('id', 'desc')->first();
                $nextNum = 1;

                if ($lastInvoice && str_contains($lastInvoice->invoice_number, '/SI/TTP/')) {
                    $parts = explode('/', $lastInvoice->invoice_number);
                    if (is_numeric($parts[0])) {
                        $nextNum = intval($parts[0]) + 1;
                    }
                }

                $romans = [
                    1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
                    7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
                ];

                $date = $invoice->invoice_date ? Carbon::parse($invoice->invoice_date) : now();
                $month = $romans[$date->month];
                $year = $date->year;

                // Format: 932/SI/TTP/VII/2026
                $invoice->invoice_number = $nextNum . '/SI/TTP/' . $month . '/' . $year;
            }
        });

        // Event SAVING: Kalkulasi Due Date (berlaku saat create maupun update)
        static::saving(function ($invoice) {
            if ($invoice->invoice_date) {
                // Jika payment_term_days kosong/null, anggap 0 (TUNAI)
                $top = $invoice->payment_term_days ?? 0;
                $invoice->due_date = Carbon::parse($invoice->invoice_date)->addDays($top);
            }
        });
    }

    protected $fillable = [
        'invoice_number',
        'delivery_order_id',
        'customer_id',
        'invoice_date',
        'payment_term_days',
        'due_date',
        'subtotal',
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
