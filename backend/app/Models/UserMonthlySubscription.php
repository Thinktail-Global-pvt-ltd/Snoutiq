<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMonthlySubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'transaction_id',
        'order_reference',
        'payment_reference',
        'status',
        'amount_paise',
        'starts_at',
        'expires_at',
        'activated_at',
        'metadata',
    ];

    protected $casts = [
        'amount_paise' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'activated_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
