<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationShareSession extends Model
{
    protected $fillable = [
        'session_token',
        'clinic_id',
        'doctor_id',
        'user_id',
        'pet_id',
        'parent_name',
        'parent_phone',
        'pet_name',
        'pet_type',
        'pet_breed',
        'amount_paise',
        'response_time_minutes',
        'status',
        'razorpay_payment_link_id',
        'razorpay_payment_link_url',
        'razorpay_short_code',
        'initiated_at',
        'payment_link_sent_at',
        'paid_at',
        'last_inbound_message_at',
        'meta',
    ];

    protected $casts = [
        'amount_paise' => 'integer',
        'response_time_minutes' => 'integer',
        'initiated_at' => 'datetime',
        'payment_link_sent_at' => 'datetime',
        'paid_at' => 'datetime',
        'last_inbound_message_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
}
