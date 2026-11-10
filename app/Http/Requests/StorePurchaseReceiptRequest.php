<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Role check dilakukan oleh middleware role:admin pada route.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'receipt_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],

            // key items = purchase_order_item_id, value = qty diterima sekarang
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'receipt_date.required' => 'Tanggal receipt wajib diisi.',
            'items.required' => 'Minimal satu item harus diisi qty diterimanya.',
            'items.*.min' => 'Qty diterima tidak boleh negatif.',
        ];
    }
}
