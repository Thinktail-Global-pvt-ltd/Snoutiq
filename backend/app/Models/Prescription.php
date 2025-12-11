<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'user_id',
        'content_html',
        'image_path',
        'next_medicine_day',
        'next_visit_day',
        'temperature',
        'temperature_unit',
    ];

    protected $casts = [
        'next_medicine_day' => 'date',
        'next_visit_day' => 'date',
        'temperature' => 'float',
    ];
}
