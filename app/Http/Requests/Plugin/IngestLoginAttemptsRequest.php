<?php

namespace App\Http\Requests\Plugin;

use Illuminate\Foundation\Http\FormRequest;

class IngestLoginAttemptsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attempts' => ['required', 'array'],
            'attempts.*.username' => ['nullable', 'string'],
            'attempts.*.ip_address' => ['nullable', 'string'],
            'attempts.*.success' => ['nullable', 'boolean'],
            'attempts.*.attempted_at' => ['nullable', 'date'],
        ];
    }
}
