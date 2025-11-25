<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallRecording extends Model
{
    protected $fillable = [
        'call_session_id',
        'call_identifier',
        'doctor_id',
        'patient_id',
        'recording_disk',
        'recording_path',
        'recording_name',
        'recording_url',
        'recording_status',
        'recording_size',
        'metadata',
        'recorded_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'recorded_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(CallSession::class);
    }
}
