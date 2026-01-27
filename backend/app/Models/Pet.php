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
        'pet_type',
        'pet_dob',
        'microchip_number',
        'mcd_registration_number',
        'is_neutered',
        'pet_doc1',
        'pet_doc2',
        'pet_card_for_ai',
        'video_calling_upload_file',
        'weight',
        'temprature',
        'vaccenated_yes_no',
        'last_vaccenated_date',
        'vaccination_date',
        'vaccine_reminder_status',
    ];

    protected $casts = [
        'pet_age' => 'integer',
        'pet_age_months' => 'integer',
        'pet_dob' => 'date',
        'vaccenated_yes_no' => 'boolean',
        'last_vaccenated_date' => 'date',
        'vaccination_date' => 'date',
        'vaccine_reminder_status' => 'array',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
