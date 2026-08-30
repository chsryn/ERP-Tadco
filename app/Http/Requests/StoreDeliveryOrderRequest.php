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
            'qty.*' => ['nullable', 'numeric', 'min:0.0001'],

            'tier_code' => ['nullable', 'array'],
            'tier_code.*' => ['nullable', 'in:S1,S2,S3,S4,S5'],
        ];
    }

    public function messages(): array
    {
        return [
            'planned_delivery_date.after_or_equal' => 'Rencana Tanggal Kirim tidak boleh mendahului Tanggal DO.',
        ];
    }

    public function items(): array
    {
        $rows = [];
        $productIds = $this->input('product_id', []);
        $qtys = $this->input('qty', []);
        $tierCodes = $this->input('tier_code', []);

        foreach ($productIds as $index => $productId) {
            $qty = $qtys[$index] ?? null;
            $tierCode = $tierCodes[$index] ?? null;

            if ($productId && $qty) {
                $rows[] = [
                    'product_id' => (int) $productId,
                    'qty' => (float) $qty,
                    'tier_code' => $tierCode,
                ];
            }
        }

        return $rows;
    }
}
