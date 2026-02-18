<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'doctor_id',
        'user_id',
        'pet_id',
        'amount_paise',
        'actual_amount_paid_by_consumer_paise',
        'payment_to_snoutiq_paise',
        'payment_to_doctor_paise',
        'status',
        'type',
        'channel_name',
        'payment_method',
        'reference',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'amount_paise' => 'integer',
        'actual_amount_paid_by_consumer_paise' => 'integer',
        'payment_to_snoutiq_paise' => 'integer',
        'payment_to_doctor_paise' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function clinic(): BelongsTo
    {
        // Transactions store clinic_id pointing to vet_registerations_temp
        return $this->belongsTo(VetRegisterationTemp::class, 'clinic_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function scopeCompleted($query)
    {
        $successfulStatuses = [
            'completed',
            'captured',
            'paid',
            'success',
            'successful',
            'settled',
        ];

        return $query->whereIn('status', $successfulStatuses);
    }

    public function scopeStatus($query, ?string $status)
    {
        if ($status) {
            $query->where('status', $status);
        }

        return $query;
    }
}
