<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HealthPulseEntry extends Model
{
    protected $fillable = [
        'user_id',
        'pet_id',
        'entry_date',
        'food',
        'energy',
        'water',
        'symptoms',
        'digestion_issue',
        'digestion_note',
        'ai_flag_level',
        'ai_short_summary',
        'ai_pattern_observation',
        'ai_recommended_action',
        'ai_payload',
        'ai_analyzed_at',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'digestion_issue' => 'boolean',
        'ai_payload' => 'array',
        'ai_analyzed_at' => 'datetime',
    ];

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function symptomAnalysis(): HasOne
    {
        return $this->hasOne(HealthPulseSymptomAnalysis::class);
    }
}
