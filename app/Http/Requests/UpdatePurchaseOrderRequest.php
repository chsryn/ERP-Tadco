<?php

namespace App\Http\Requests;

/**
 * Draft PO memakai aturan validasi yang sama dengan pembuatan PO baru.
 * Perbedaan perilaku (hanya draft yang boleh diubah) dijaga di service & controller.
 */
class UpdatePurchaseOrderRequest extends StorePurchaseOrderRequest
{
}
