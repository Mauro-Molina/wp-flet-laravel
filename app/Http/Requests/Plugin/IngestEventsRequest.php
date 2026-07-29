<?php

namespace App\Http\Requests\Plugin;

use Illuminate\Foundation\Http\FormRequest;

class IngestEventsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'events' => ['required', 'array', 'min:1'],
            'events.*.event_type' => ['required', 'string', 'max:128'],
            'events.*.payload' => ['nullable', 'array'],
            'events.*.occurred_at' => ['nullable', 'date'],
        ];
    }
}
