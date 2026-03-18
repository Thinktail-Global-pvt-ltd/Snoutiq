<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PetFeedback extends Model
{
    protected $table = 'pet_feedback';

    protected $fillable = [
        'pet_id',
        'vet_id',
        'user_id',
        'channel_name',
        'rating',
        'feedback',
        'source',
        'meta',
    ];

    protected $casts = [
        'pet_id' => 'integer',
        'vet_id' => 'integer',
        'user_id' => 'integer',
        'rating' => 'integer',
        'meta' => 'array',
    ];
}
