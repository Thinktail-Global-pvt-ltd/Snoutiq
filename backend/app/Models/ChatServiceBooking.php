<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatServiceBooking extends Model
{
    use HasFactory;

    protected $table = 'chat_service_bookings';

    protected $fillable = [
        'booking_reference',
        'session_id',
        'user_id',
        'pet_id',
        'consultation_type',
        'booking_status',
        'slot_id',
        'scheduled_date',
        'scheduled_time',
        'scheduled_for',
        'timezone',
        'doctor_id',
        'clinic_id',
        'external_place_id',
        'clinic_name',
        'doctor_name',
        'address',
        'phone',
        'maps_link',
        'price',
        'currency',
        'source_tool',
        'booking_payload',
        'notes',
    ];

    protected $casts = [
        'booking_payload' => 'array',
        'scheduled_for' => 'datetime',
        'price' => 'float',
    ];
}
