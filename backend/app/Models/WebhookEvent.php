<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'source', 'event', 'signature', 'payload', 'processed_at', 'retries'
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];
}

