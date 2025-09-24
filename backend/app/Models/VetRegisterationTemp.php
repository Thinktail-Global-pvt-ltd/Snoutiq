<?php

// namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;

// class VetRegisterationTemp extends Model
// {
//     use HasFactory;

//     protected $table = 'vet_registerations_temp';

// protected $fillable = [
//      'mobile',
//     'image',
//     'name',      // already exists, keep it
//     'email',     // new
//     'city',
//     'pincode',
//     'license_no',
//     'coordinates',
//     'address',
//     'chat_price',
//     'bio',
//     'password',
//     'hospital_profile',
//     'clinic_profile',
//     'employee_id',

//     // Google place fields...
//     'place_id',
//     'business_status',
//     'formatted_address',
//     'lat',
//     'lng',
//     'viewport_ne_lat',
//     'viewport_ne_lng',
//     'viewport_sw_lat',
//     'viewport_sw_lng',
//     'icon',
//     'icon_background_color',
//     'icon_mask_base_uri',
//     'open_now',
//     'photos',
//     'types',
//     'compound_code',
//     'global_code',
//     'rating',
//     'user_ratings_total',
// ];
// public function doctors()
// {
//     return $this->hasMany(Doctor::class, 'vet_registeration_id');
// }

// }


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class VetRegisterationTemp extends Model
{
    use HasFactory;

    protected $table = 'vet_registerations_temp';

    protected $fillable = [
        'mobile',
        'image',
        'name',
        'email',
        'city',
        'pincode',
        'license_no',
        'coordinates',
        'address',
        'chat_price',
        'bio',
        'password',
        'hospital_profile',
        'clinic_profile',
        'employee_id',
        'place_id',
        'business_status',
        'formatted_address',
        'lat',
        'lng',
        'viewport_ne_lat',
        'viewport_ne_lng',
        'viewport_sw_lat',
        'viewport_sw_lng',
        'icon',
        'icon_background_color',
        'icon_mask_base_uri',
        'open_now',
        'photos',
        'types',
        'compound_code',
        'global_code',
        'rating',
        'user_ratings_total',
        'slug', // ✅ new
    ];

    public function doctors()
    {
        return $this->hasMany(Doctor::class, 'vet_registeration_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($vet) {
            if (empty($vet->slug) && !empty($vet->name)) {
                $vet->slug = Str::slug($vet->name);

                // ensure uniqueness
                $original = $vet->slug;
                $i = 1;
                while (static::where('slug', $vet->slug)->where('id', '!=', $vet->id)->exists()) {
                    $vet->slug = $original.'-'.$i++;
                }
            }
        });
    }
}

