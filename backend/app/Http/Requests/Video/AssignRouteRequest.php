<?php

declare(strict_types=1);

namespace App\Http\Requests\Video;

use Illuminate\Foundation\Http\FormRequest;

class AssignRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // public routing ok, or add guard
    }

    public function rules(): array
    {
        return [
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
            'ts' => 'nullable|date', // ISO8601 with IST offset
        ];
    }
}

