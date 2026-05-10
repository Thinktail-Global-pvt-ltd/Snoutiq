<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VetAtHomeService extends Model
{
    protected $fillable = [
        'clinic_id',
        'doctor_id',
        'is_enabled',
        'service_hours',
        'response_time',
        'base_payout',
        'protocol_label',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'base_payout' => 'decimal:2',
    ];

    public function clinic()
    {
        return $this->belongsTo(VetRegisterationTemp::class, 'clinic_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
