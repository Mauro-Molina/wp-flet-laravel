<?php

namespace App\Http\Requests\Plugin;

use Illuminate\Foundation\Http\FormRequest;

class HeartbeatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plugins' => ['nullable', 'array'],
            'themes' => ['nullable', 'array'],
            'wp_version' => ['nullable', 'string', 'max:32'],
            'php_version' => ['nullable', 'string', 'max:32'],
            'wordpress_version' => ['nullable', 'string', 'max:32'],
            'runtime' => ['nullable', 'array'],
            'runtime.wp_version' => ['nullable', 'string', 'max:32'],
            'runtime.php_version' => ['nullable', 'string', 'max:32'],
        ];
    }
}
