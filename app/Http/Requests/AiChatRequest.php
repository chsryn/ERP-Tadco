<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AiChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Role check dilakukan oleh middleware role pada route.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'prompt' => [
                'required',
                'string',
                'min:3',
                'max:'.config('ai.max_prompt_length', 1000),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'prompt.required' => 'Pertanyaan tidak boleh kosong.',
            'prompt.min' => 'Pertanyaan terlalu pendek.',
            'prompt.max' => 'Pertanyaan melebihi batas :max karakter.',
        ];
    }
}
