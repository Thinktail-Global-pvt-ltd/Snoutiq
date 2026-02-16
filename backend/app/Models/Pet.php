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
        'gender',
        'type',
        'dob',
        'microchip_number',
        'mcd_registration_number',
        'is_neutered',
        'pet_doc1',
        'pet_doc2',
        'pet_doc2_blob',
        'pet_doc2_mime',
        'pet_card_for_ai',
        'video_calling_upload_file',
        'weight',
        'temprature',
        'vaccenated_yes_no',
        'last_vaccenated_date',
        'vaccination_date',
        'vaccine_reminder_status',
        'dog_disease_payload',
        'medical_history',
        'vaccination_log',
        'pic_link',
    ];

    protected $casts = [
        'pet_age' => 'integer',
        'pet_age_months' => 'integer',
        'pet_dob' => 'date',
        'vaccenated_yes_no' => 'boolean',
        'last_vaccenated_date' => 'date',
        'vaccination_date' => 'date',
        'vaccine_reminder_status' => 'array',
        'dog_disease_payload' => 'array',
    ];

    protected $hidden = [
        'pet_doc2_blob',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
