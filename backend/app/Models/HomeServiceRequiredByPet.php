<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeServiceRequiredByPet extends Model
{
    protected $table = 'home_service_required_by_pet';

    protected $fillable = [
        'user_id',
        'pet_id',
        'latest_completed_step',
        'owner_name',
        'owner_phone',
        'pet_type',
        'area',
        'reason_for_visit',
        'date_of_visit',
        'time_of_visit',
        'concern_description',
        'symptoms',
        'vaccination_status',
        'last_deworming',
        'past_illnesses_or_surgeries',
        'current_medications',
        'known_allergies',
        'vet_notes',
        'payment_status',
        'amount_payable',
        'amount_paid',
        'payment_provider',
        'payment_reference',
        'booking_reference',
        'step1_completed_at',
        'step2_completed_at',
        'step3_completed_at',
        'confirmed_at',
    ];

    protected $casts = [
        'symptoms' => 'array',
        'date_of_visit' => 'date',
        'amount_payable' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'step1_completed_at' => 'datetime',
        'step2_completed_at' => 'datetime',
        'step3_completed_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }
}
