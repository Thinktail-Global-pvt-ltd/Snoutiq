<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';

    public const CHANNEL_WHATSAPP = 'whatsapp';
    public const CHANNEL_PUSH = 'push';
    public const CHANNEL_SMS = 'sms';
    public const CHANNEL_IN_APP = 'in_app';

    protected $fillable = [
        'user_id',
        'pet_id',
        'clinic_id',
        'type',
        'title',
        'body',
        'payload',
        'debug_tokens',
        'status',
        'channel',
        'sent_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'debug_tokens' => 'array',
        'sent_at' => 'datetime',
    ];
}
