<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Role check dilakukan oleh middleware role:admin pada route.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'po_date' => ['required', 'date'],
            'period' => ['required', 'date_format:Y-m'],
            'supplier_id' => [
                'required',
                Rule::exists('suppliers', 'id')->where('is_active', true),
            ],
            'ppn_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where('is_active', true)->whereNull('deleted_at'),
            ],
            'items.*.qty_plan' => ['required', 'numeric', 'gt:0'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
            'items.*.special_discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.week_1' => ['nullable', 'numeric', 'min:0'],
            'items.*.week_2' => ['nullable', 'numeric', 'min:0'],
            'items.*.week_3' => ['nullable', 'numeric', 'min:0'],
            'items.*.week_4' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.exists' => 'Supplier tidak tersedia atau sedang nonaktif.',
            'items.required' => 'Purchase Order minimal memiliki satu item.',
            'items.min' => 'Purchase Order minimal memiliki satu item.',
            'items.*.product_id.exists' => 'Produk tidak tersedia atau sedang nonaktif.',
            'items.*.qty_plan.gt' => 'Qty plan harus lebih dari 0.',
        ];
    }
}
