<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VetFeedback extends Model
{
    protected $table = 'vet_feedback';

    protected $fillable = [
        'vet_id',
        'user_id',
        'pet_id',
        'rating',
        'feedback',
        'source',
        'meta',
    ];

    protected $casts = [
        'vet_id' => 'integer',
        'user_id' => 'integer',
        'pet_id' => 'integer',
        'rating' => 'integer',
        'meta' => 'array',
    ];
}
