<?php

namespace App\Http\Requests\Founder;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class SalesIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'status' => ['nullable', 'string', 'in:all,completed,pending,failed'],
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function limit(): int
    {
        return (int) $this->input('limit', 20);
    }

    public function status(): ?string
    {
        $status = $this->input('status');
        return $status === null || $status === 'all'
            ? null
            : $status;
    }

    public function range(): array
    {
        $from = $this->input('from');
        $to = $this->input('to');

        return [
            $from ? Carbon::parse($from) : now()->subDays(30),
            $to ? Carbon::parse($to) : now(),
        ];
    }
}
