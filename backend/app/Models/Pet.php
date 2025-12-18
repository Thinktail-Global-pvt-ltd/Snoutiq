<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pet extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'breed',
        'pet_age',
        'pet_age_months',
        'pet_gender',
        'microchip_number',
        'mcd_registration_number',
        'is_neutered',
        'pet_doc1',
        'pet_doc2',
        'weight',
        'temprature',
        'vaccenated_yes_no',
        'last_vaccenated_date',
        'vaccination_date',
    ];

    protected $casts = [
        'pet_age' => 'integer',
        'pet_age_months' => 'integer',
        'vaccenated_yes_no' => 'boolean',
        'last_vaccenated_date' => 'date',
        'vaccination_date' => 'date',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
