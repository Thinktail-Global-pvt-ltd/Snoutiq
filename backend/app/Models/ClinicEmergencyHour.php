<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicEmergencyHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'doctor_ids',
        'night_slots',
        'consultation_price',
    ];

    protected $casts = [
        'doctor_ids' => 'array',
        'night_slots' => 'array',
        'consultation_price' => 'float',
    ];
}
