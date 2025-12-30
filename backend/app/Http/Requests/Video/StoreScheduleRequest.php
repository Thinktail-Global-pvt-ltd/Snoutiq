<?php

declare(strict_types=1);

namespace App\Http\Requests\Video;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Allow third-party access without authentication (explicit request)
        return true;
    }

    public function rules(): array
    {
        return [
            'avg_consult_minutes' => 'required|integer|min:5|max:120',
            'max_bookings_per_hour' => 'required|integer|min:1|max:10',
            'is_247' => 'required|boolean',
            'days' => 'required|array|size:7',
            'days.*.dow' => 'required|integer|min:0|max:6',
            'days.*.active' => 'required|boolean',
            'days.*.start_time' => 'nullable|string',
            'days.*.end_time' => 'nullable|string',
            'days.*.break_start_time' => 'nullable|string',
            'days.*.break_end_time' => 'nullable|string',
        ];
    }
}
