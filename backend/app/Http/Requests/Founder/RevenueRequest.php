<?php

namespace App\Http\Requests\Founder;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class RevenueRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'group' => ['nullable', 'string', 'in:day,month'],
            'includeProjections' => ['nullable', 'boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function grouping(): string
    {
        return $this->input('group', 'month');
    }

    public function includeProjections(): bool
    {
        return (bool) $this->boolean('includeProjections', true);
    }

    public function range(): array
    {
        $from = $this->input('from');
        $to = $this->input('to');

        return [
            $from ? Carbon::parse($from) : now()->subMonths(6),
            $to ? Carbon::parse($to) : now(),
        ];
    }
}

