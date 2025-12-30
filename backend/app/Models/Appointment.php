<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'vet_registeration_id',
        'doctor_id',
        'name',
        'mobile',
        'pet_name',
        'appointment_date',
        'appointment_time',
        'status',
        'notes',
        'reminder_24h_sent_at',
        'reminder_3h_sent_at',
        'reminder_30m_sent_at',
    ];

    protected $casts = [
        'reminder_24h_sent_at' => 'datetime',
        'reminder_3h_sent_at' => 'datetime',
        'reminder_30m_sent_at' => 'datetime',
    ];

    public function clinic()
    {
        return $this->belongsTo(VetRegisterationTemp::class, 'vet_registeration_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
}
