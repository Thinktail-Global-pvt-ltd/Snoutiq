<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'vet_registeration_id',
        'doctor_name',
        'doctor_email',
        'doctor_mobile',
        'doctor_license',
        'doctor_document',
        'doctor_image',
        'toggle_availability',
    ];

    /**
     * Doctor belongs to one Vet Registeration
     */
    public function vet()
    {
        return $this->belongsTo(VetRegisterationTemp::class, 'vet_registeration_id');
    }
      public function clinic()
    {
        return $this->belongsTo(VetRegisterationTemp::class, 'vet_registeration_id');
    }
}
