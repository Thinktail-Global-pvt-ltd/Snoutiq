<?php

namespace App\Http\Requests\Founder;

use Illuminate\Foundation\Http\FormRequest;

class DashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'mode' => ['nullable', 'string', 'in:live,testing,pre-launch'],
            'period' => ['nullable', 'string', 'in:6m,1y,all'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function mode(): string
    {
        return $this->input('mode', 'pre-launch');
    }

    public function period(): string
    {
        return $this->input('period', '6m');
    }
}

