<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserObservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pet_id',
        'eating',
        'appetite',
        'energy',
        'mood',
        'symptoms',
        'notes',
        'image_blob',
        'image_mime',
        'image_name',
        'observed_at',
    ];

    protected $hidden = [
        'image_blob',
    ];

    protected $casts = [
        'symptoms' => 'array',
        'observed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
