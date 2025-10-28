<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pet extends Model
{
    protected $fillable = [
        'user_id', 'name', 'breed', 'pet_age', 'pet_gender', 'pet_doc1', 'pet_doc2'
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
