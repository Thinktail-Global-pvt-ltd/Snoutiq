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
        'status',
        'type',
        'payment_method',
        'reference',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
