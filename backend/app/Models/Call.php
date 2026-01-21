<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Call extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_RINGING = 'ringing';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_MISSED = 'missed';
    public const STATUS_ENDED = 'ended';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'doctor_id',
        'patient_id',
        'status',
        'channel',
        'rtc',
        'accepted_at',
        'rejected_at',
        'ended_at',
        'cancelled_at',
        'missed_at',
    ];

    protected $casts = [
        'rtc' => 'array',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'ended_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'missed_at' => 'datetime',
    ];
}
