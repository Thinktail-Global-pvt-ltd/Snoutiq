<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DoctorWeeklyVideoSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'avg_consult_minutes',
        'max_bookings_per_hour',
        'is_247',
    ];

    public function days(): HasMany
    {
        return $this->hasMany(DoctorWeeklyVideoScheduleDay::class, 'schedule_id');
    }
}

