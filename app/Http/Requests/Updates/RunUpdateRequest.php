<?php

namespace App\Http\Requests\Updates;

use Illuminate\Foundation\Http\FormRequest;

class RunUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updates.run') ?? false;
    }

    public function rules(): array
    {
        return [
            'update_type' => ['required', 'string', 'in:core,plugin,theme'],
            'idempotency_key' => ['required', 'string', 'max:128'],
            'items' => ['nullable', 'array'],
            'items.*' => ['string'],
        ];
    }
}
