<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'do_date' => ['required', 'date'],
            'planned_delivery_date' => ['nullable', 'date', 'after_or_equal:do_date'],
            'notes' => ['nullable', 'string'],

            'product_id' => ['required', 'array'],
            'product_id.*' => ['nullable', 'exists:products,id'],

            'qty' => ['required', 'array'],
            // Kolom qty DO/invoice bertipe integer. Jangan menerima desimal
            // agar tidak terjadi pemotongan presisi diam-diam.
            'qty.*' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'planned_delivery_date.after_or_equal' => 'Rencana Tanggal Kirim tidak boleh mendahului Tanggal DO.',
            'qty.*.integer' => 'Qty harus bilangan bulat. Qty desimal tidak didukung.',
            'qty.*.min' => 'Qty minimal 1.',
        ];
    }

    public function items(): array
    {
        $rows = [];
        $productIds = $this->input('product_id', []);
        $qtys = $this->input('qty', []);

        foreach ($productIds as $index => $productId) {
            $qty = $qtys[$index] ?? null;

            if ($productId && $qty !== null && $qty !== '') {
                $rows[] = [
                    'product_id' => (int) $productId,
                    'qty' => (int) $qty,
                ];
            }
        }

        return $rows;
    }
}
