<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consultation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pet_id',
        'clinic_id',
        'doctor_id',
        'booking_id',
        'call_session_id',
        'mode',
        'start_time',
        'end_time',
        'status',
        'user_joined_at',
        'cancelled_at',
        'no_show_marked_at',
        'follow_up_after_days',
        'follow_up_due_at',
        'reminder_24h_sent_at',
        'reminder_3h_sent_at',
        'reminder_30m_sent_at',
        'meta',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'user_joined_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'no_show_marked_at' => 'datetime',
        'follow_up_due_at' => 'datetime',
        'reminder_24h_sent_at' => 'datetime',
        'reminder_3h_sent_at' => 'datetime',
        'reminder_30m_sent_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
