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
        'puppy_vaccination_package_price',
        'adult_dog_vaccination_package_price',
        'kitten_vaccination_package_price',
        'adult_cat_vaccination_package_price',
        'dog_vaccination_male_package_price',
        'dog_vaccination_female_package_price',
        'cat_vaccination_male_package_price',
        'cat_vaccination_female_package_price',
        'dog_neutering_male_price',
        'dog_neutering_female_price',
        'cat_neutering_male_price',
        'cat_neutering_female_price',
    ];

    protected $casts = [
        'dog_vaccination_package_price' => 'decimal:2',
        'cat_vaccination_package_price' => 'decimal:2',
        'dog_neutering_price' => 'decimal:2',
        'cat_neutering_price' => 'decimal:2',
        'puppy_vaccination_package_price' => 'decimal:2',
        'adult_dog_vaccination_package_price' => 'decimal:2',
        'kitten_vaccination_package_price' => 'decimal:2',
        'adult_cat_vaccination_package_price' => 'decimal:2',
        'dog_vaccination_male_package_price' => 'decimal:2',
        'dog_vaccination_female_package_price' => 'decimal:2',
        'cat_vaccination_male_package_price' => 'decimal:2',
        'cat_vaccination_female_package_price' => 'decimal:2',
        'dog_neutering_male_price' => 'decimal:2',
        'dog_neutering_female_price' => 'decimal:2',
        'cat_neutering_male_price' => 'decimal:2',
        'cat_neutering_female_price' => 'decimal:2',
    ];

    public function getPuppyVaccinationPackagePriceAttribute($value)
    {
        return $value ?? $this->attributes['dog_vaccination_male_package_price'] ?? $this->attributes['dog_vaccination_package_price'] ?? null;
    }

    public function getAdultDogVaccinationPackagePriceAttribute($value)
    {
        return $value ?? $this->attributes['dog_vaccination_female_package_price'] ?? $this->attributes['dog_vaccination_package_price'] ?? null;
    }

    public function getKittenVaccinationPackagePriceAttribute($value)
    {
        return $value ?? $this->attributes['cat_vaccination_male_package_price'] ?? $this->attributes['cat_vaccination_package_price'] ?? null;
    }

    public function getAdultCatVaccinationPackagePriceAttribute($value)
    {
        return $value ?? $this->attributes['cat_vaccination_female_package_price'] ?? $this->attributes['cat_vaccination_package_price'] ?? null;
    }

    public function clinic()
    {
        return $this->belongsTo(VetRegisterationTemp::class, 'clinic_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
