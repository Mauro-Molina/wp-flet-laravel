<?php

namespace App\Http\Requests\Backups;

use Illuminate\Foundation\Http\FormRequest;

class RestoreBackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('backups.restore') ?? false;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'max:128'],
            'site_name_confirmation' => ['required', 'string', 'max:255'],
            'confirmed_destructive' => ['required', 'boolean', 'accepted'],
        ];
    }
}
