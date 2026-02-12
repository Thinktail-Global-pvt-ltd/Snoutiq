<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorFcmToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'token',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
