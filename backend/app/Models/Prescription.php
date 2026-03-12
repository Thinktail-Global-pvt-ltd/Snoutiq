<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'doctor_treatment',
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
        'disease_name',
        'prognosis',
        'is_chronic',
        'treatment_plan',
        'medications_json',
        'home_care',
        'history_snapshot',
        'video_inclinic',
        'call_session',
        'follow_up_required',
        'follow_up_date',
        'follow_up_notification_sent_at',
        'follow_up_type',
        'follow_up_notes',
        'system_affected',
        'system_affected_id',
        'mucous_membrane',
        'dehydration_level',
        'abdominal_pain_reaction',
        'auscultation',
        'physical_exam_other',
        'pet_id',
        'video_appointment_id',
        'in_clinic_appointment_id',
    ];

    protected $casts = [
        'next_medicine_day' => 'date',
        'next_visit_day' => 'date',
        'temperature' => 'float',
        'weight' => 'float',
        'heart_rate' => 'float',
        'system_affected_id' => 'integer',
        'follow_up_required' => 'boolean',
        'follow_up_date' => 'date',
        'follow_up_notification_sent_at' => 'datetime',
        'is_chronic' => 'boolean',
        'medications_json' => 'array',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class, 'pet_id');
    }

    public function systemAffected(): BelongsTo
    {
        return $this->belongsTo(AffectedSystem::class, 'system_affected_id');
    }
}
