<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorWeeklyVideoScheduleDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'dow',
        'active',
        'start_time',
        'end_time',
        'break_start_time',
        'break_end_time',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(DoctorWeeklyVideoSchedule::class, 'schedule_id');
    }
}

