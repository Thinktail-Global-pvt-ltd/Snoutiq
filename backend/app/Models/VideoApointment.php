<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoApointment extends Model
{
    protected $table = 'video_apointment';

    protected $fillable = [
        'order_id',
        'pet_id',
        'user_id',
        'doctor_id',
        'clinic_id',
        'call_session',
        'is_completed',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
    ];
}

