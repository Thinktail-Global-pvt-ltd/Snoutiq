<?php

namespace App\Http\Requests\Founder;

use Illuminate\Foundation\Http\FormRequest;

class SettingUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'notifications.enabled' => ['nullable', 'boolean'],
            'theme' => ['nullable', 'string', 'in:light,dark'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function payload(): array
    {
        $data = [];

        if ($this->has('notifications.enabled')) {
            $data['notifications']['enabled'] = (bool) $this->boolean('notifications.enabled');
        }

        if ($this->filled('theme')) {
            $data['theme'] = $this->input('theme');
        }

        return $data;
    }
}

