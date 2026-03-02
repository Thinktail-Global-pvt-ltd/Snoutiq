<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PetDailyCare extends Model
{
    protected $fillable = [
        'user_id',
        'pet_id',
        'care_date',
        'task_key',
        'title',
        'scheduled_time',
        'icon',
        'is_completed',
        'completed_at',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'care_date' => 'date',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'sort_order' => 'integer',
    ];
}
