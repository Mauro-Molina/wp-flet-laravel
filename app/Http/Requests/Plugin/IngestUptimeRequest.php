<?php

namespace App\Http\Requests\Plugin;

use Illuminate\Foundation\Http\FormRequest;

class IngestUptimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_up' => ['required', 'boolean'],
            'response_time_ms' => ['nullable', 'integer', 'min:0'],
            'http_status' => ['nullable', 'integer'],
            'performance' => ['nullable', 'array'],
            'checked_at' => ['nullable', 'date'],
        ];
    }
}
