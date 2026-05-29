<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthPulseSymptomAnalysis extends Model
{
    protected $fillable = [
        'user_id',
        'pet_id',
        'health_pulse_entry_id',
        'entry_date',
        'symptom_entry_count',
        'symptoms_snapshot',
        'analysis_text',
        'flag_level',
        'recommended_action',
        'ai_payload',
        'analyzed_at',
    ];

    protected $casts = [
        'symptoms_snapshot' => 'array',
        'ai_payload' => 'array',
        'analyzed_at' => 'datetime',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(HealthPulseEntry::class, 'health_pulse_entry_id');
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
