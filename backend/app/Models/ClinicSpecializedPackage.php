<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicSpecializedPackage extends Model
{
    protected $fillable = [
        'clinic_id',
        'doctor_id',
        'dog_vaccination_package_price',
        'cat_vaccination_package_price',
        'dog_neutering_price',
        'cat_neutering_price',
    ];

    protected $casts = [
        'dog_vaccination_package_price' => 'decimal:2',
        'cat_vaccination_package_price' => 'decimal:2',
        'dog_neutering_price' => 'decimal:2',
        'cat_neutering_price' => 'decimal:2',
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
