<?php

namespace App\Http\Requests\Founder;

use App\Models\Alert;
use Illuminate\Foundation\Http\FormRequest;

class AlertIndexRequest extends FormRequest
{
    public function rules(): array
    {
        $types = implode(',', array_merge(['all'], Alert::TYPES));

        return [
            'type' => ['nullable', 'string', 'in:'.$types],
            'is_read' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function type(): ?string
    {
        $type = $this->input('type');
        return $type === 'all' ? null : $type;
    }

    public function limit(): int
    {
        return (int) $this->input('limit', 20);
    }

    public function readFilter(): ?bool
    {
        if (! $this->has('is_read')) {
            return null;
        }

        return $this->boolean('is_read');
    }
}

