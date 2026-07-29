<?php

namespace App\Http\Requests\Plugin;

use Illuminate\Foundation\Http\FormRequest;

class IngestSecurityScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scan_type' => ['required', 'string', 'in:malware,vulnerability,integrity'],
            'status' => ['nullable', 'string'],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'findings' => ['nullable', 'array'],
            'scanned_at' => ['nullable', 'date'],
        ];
    }
}
