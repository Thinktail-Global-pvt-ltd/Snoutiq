<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'medical_record_id',
        'doctor_id',
        'user_id',
        'visit_category',
        'case_severity',
        'visit_notes',
        'content_html',
        'image_path',
        'next_medicine_day',
        'next_visit_day',
        'temperature',
        'temperature_unit',
        'weight',
        'heart_rate',
        'exam_notes',
        'diagnosis',
        'diagnosis_status',
        'treatment_plan',
        'home_care',
        'follow_up_date',
        'follow_up_type',
        'follow_up_notes',
        'pet_id',
    ];

    protected $casts = [
        'next_medicine_day' => 'date',
        'next_visit_day' => 'date',
        'temperature' => 'float',
        'weight' => 'float',
        'heart_rate' => 'float',
        'follow_up_date' => 'date',
    ];
}
