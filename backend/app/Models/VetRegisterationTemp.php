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
        'license_document',
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
        'public_id',
        'claim_token',
        'status',
        'owner_user_id',
        'draft_created_by_user_id',
        'draft_expires_at',
        'claimed_at',
        'qr_code_path',
    ];

    protected $casts = [
        'draft_expires_at' => 'datetime',
        'claimed_at' => 'datetime',
    ];

    public function doctors()
    {
        return $this->hasMany(Doctor::class, 'vet_registeration_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $vet) {
            if (empty($vet->public_id)) {
                $vet->public_id = Str::ulid()->toBase32();
            }

            if (empty($vet->status)) {
                $vet->status = 'draft';
            }

            if ($vet->status === 'draft' && empty($vet->claim_token)) {
                $vet->claim_token = Str::random(32);
            }

            if ($vet->status === 'draft' && empty($vet->draft_expires_at)) {
                $vet->draft_expires_at = now()->addDays(60);
            }
        });

        static::saving(function (self $vet) {
            if (empty($vet->public_id)) {
                $vet->public_id = Str::ulid()->toBase32();
            }

            if ($vet->status !== 'draft') {
                $vet->draft_expires_at = null;
            } elseif (empty($vet->draft_expires_at)) {
                $vet->draft_expires_at = now()->addDays(60);
            }

            $slugSource = $vet->name ?: ('clinic-'.$vet->public_id);
            if (empty($vet->slug)) {
                $vet->slug = Str::slug($slugSource);
            }

            // ensure slug uniqueness
            $original = $vet->slug;
            $suffix = 1;
            while (
                static::where('slug', $vet->slug)
                    ->where('id', '!=', $vet->id)
                    ->exists()
            ) {
                $vet->slug = $original.'-'.$suffix++;
            }
        });
    }
}
