<?php

namespace App\Http\Requests\Commands;

use App\Models\Command;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Command::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:128'],
            'idempotency_key' => ['required', 'string', 'max:128'],
            'payload' => ['nullable', 'array'],
        ];
    }
}
