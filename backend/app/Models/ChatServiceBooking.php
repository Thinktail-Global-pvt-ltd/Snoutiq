<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatServiceBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pet_id',
        'vet_registeration_id',
        'doctor_id',
        'chat_room_token',
        'context_token',
        'service_type',
        'appointment_date',
        'appointment_time',
        'notes',
        'source_payload',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'source_payload' => 'array',
    ];
}
