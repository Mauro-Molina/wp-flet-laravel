<?php

namespace App\Http\Requests\Sites;

use App\Models\Site;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $site = Site::query()->find($this->route('site'));

        return $site !== null && $this->user()?->can('update', $site);
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'url' => ['sometimes', 'url', 'max:255'],
        ];
    }
}
