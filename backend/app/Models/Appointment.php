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

