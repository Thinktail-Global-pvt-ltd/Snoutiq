<?php

namespace App\Models;
use App\Models\VetRegisterationTemp;
use Illuminate\Database\Eloquent\Model;


class DoctorBooking extends Model
{
    protected $table = 'doctor_bookings';

    protected $fillable = [
        'serial_number',
        'customer_id',
        'date',
        'start_time',
        'end_time',
        'services',
        'total',
        'paid',
        'user_id',
        'vet_id',
        'status'
    ];

    public function vet()
    {
        return $this->belongsTo(VetRegisterationsTemp::class, 'vet_id');
    }
}
