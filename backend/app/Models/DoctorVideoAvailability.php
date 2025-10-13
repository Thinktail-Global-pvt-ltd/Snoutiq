<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorVideoAvailability extends Model
{
    use HasFactory;

    protected $table = 'doctor_video_availability';

    protected $fillable = [
        'doctor_id',
        'day_of_week',
        'start_time',
        'end_time',
        'break_start',
        'break_end',
        'avg_consultation_mins',
        'max_bookings_per_hour',
        'is_active',
    ];
}

