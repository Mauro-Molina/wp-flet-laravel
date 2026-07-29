<?php

namespace App\Http\Requests\Plugin;

use Illuminate\Foundation\Http\FormRequest;

class SyncUpdatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'updates' => ['required', 'array'],
            'updates.*.update_type' => ['required', 'string', 'in:core,plugin,theme'],
            'updates.*.item_slug' => ['required', 'string'],
            'updates.*.item_name' => ['nullable', 'string'],
            'updates.*.current_version' => ['nullable', 'string'],
            'updates.*.available_version' => ['nullable', 'string'],
        ];
    }
}
