<?php

namespace App\Http\Requests\Founder;

use Illuminate\Foundation\Http\FormRequest;

class ClinicIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:all,active,inactive'],
            'search' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', 'string', 'regex:/^[a-z_]+:(asc|desc)$/i'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function status(): ?string
    {
        $status = $this->input('status');
        return $status === 'all' ? null : $status;
    }

    public function limit(): int
    {
        return (int) $this->input('limit', 20);
    }

    public function sort(): array
    {
        $raw = $this->input('sort', 'created_at:desc');
        [$field, $direction] = array_pad(explode(':', $raw, 2), 2, 'desc');

        return [
            strtolower($field) ?: 'created_at',
            strtolower($direction) === 'asc' ? 'asc' : 'desc',
        ];
    }
}

