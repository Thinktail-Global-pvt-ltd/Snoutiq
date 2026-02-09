<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VetResponseReminderLog extends Model
{
    protected $fillable = [
        'transaction_id',
        'user_id',
        'pet_id',
        'phone',
        'template',
        'language',
        'status',
        'error',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
