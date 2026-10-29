<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Counter nomor dokumen per (document_type, period).
 * Diubah hanya lewat PurchaseOrderService::nextNumber() di dalam DB transaction.
 */
class DocumentSequence extends Model
{
    protected $fillable = [
        'document_type',
        'period',
        'last_number',
    ];

    protected $casts = [
        'last_number' => 'integer',
    ];
}
