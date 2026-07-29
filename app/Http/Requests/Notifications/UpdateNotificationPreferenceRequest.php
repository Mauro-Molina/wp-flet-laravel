<?php

namespace App\Http\Requests\Notifications;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_type' => ['required', 'string', 'max:128'],
            'channel' => ['required', 'string', 'in:push,email,in_app'],
            'enabled' => ['required', 'boolean'],
        ];
    }
}
