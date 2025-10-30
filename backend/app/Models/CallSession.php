<?php

namespace App\Models;

use App\Support\CallSessionUrlBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallSession extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'channel_name',
        'call_identifier',
        'doctor_join_url',
        'patient_payment_url',
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
        'push_notified_at'  => 'datetime',
    ];

    public function refreshComputedLinks(?string $frontendBase = null): self
    {
        $doctorId = $this->doctor_id !== null ? (int) $this->doctor_id : null;
        if ($doctorId === 0 && $this->doctor_id !== 0) {
            $doctorId = null;
        }

        $patientId = $this->patient_id !== null ? (int) $this->patient_id : null;
        if ($patientId === 0 && $this->patient_id !== 0) {
            $patientId = null;
        }

        $base = $frontendBase ?? CallSessionUrlBuilder::frontendBase();

        $this->doctor_join_url = CallSessionUrlBuilder::doctorJoinUrl(
            $this->channel_name,
            $this->call_identifier,
            $doctorId,
            $patientId,
            $base
        );

        $this->patient_payment_url = CallSessionUrlBuilder::patientPaymentUrl(
            $this->channel_name,
            $this->call_identifier,
            $doctorId,
            $patientId,
            $base
        );

        return $this;
    }

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
