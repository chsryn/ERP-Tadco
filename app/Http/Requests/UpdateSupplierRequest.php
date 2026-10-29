<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Role check dilakukan oleh middleware role:admin pada route.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('suppliers', 'code')->ignore($this->route('supplier')),
            ],
            'name' => ['required', 'string', 'max:150'],
            'region' => ['nullable', 'string', 'max:100'],
            'port_depo' => ['nullable', 'string', 'max:100'],
            'delivery_mode' => ['nullable', 'string', 'max:100'],
            'term_of_payment_days' => ['nullable', 'integer', 'min:0'],
            'pic_name' => ['nullable', 'string', 'max:150'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
