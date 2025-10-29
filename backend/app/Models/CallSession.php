<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallSession extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'channel_name',
        'status',
        'payment_status',
        'accepted_at',
        'started_at',
        'ended_at',
        'duration_seconds',
        'payment_id',
        'amount_paid',
        'currency',
    ];

    protected $casts = [
        'accepted_at'       => 'datetime',
        'started_at'        => 'datetime',
        'ended_at'          => 'datetime',
        'duration_seconds'  => 'integer',
        'amount_paid'       => 'integer',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
}

