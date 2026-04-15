<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RazorpayPaymentLink extends Model
{
    protected $fillable = [
        'payment_link_id',
        'short_url',
        'short_code',
        'reference_id',
        'source',
        'user_id',
        'pet_id',
        'clinic_id',
        'doctor_id',
        'amount_paise',
        'currency',
        'status',
        'payment_id',
        'order_id',
        'payment_status',
        'paid_at',
        'raw_response',
        'webhook_payload',
    ];

    protected $casts = [
        'raw_response' => 'array',
        'webhook_payload' => 'array',
        'paid_at' => 'datetime',
    ];
}
