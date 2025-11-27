<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class MarketingSingleNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'token',
        'scheduled_for',
        'send_at',
        'status',
        'error_message',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'send_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
}
