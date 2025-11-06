<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ScheduledPushDispatchLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'scheduled_push_notification_id',
        'device_token_id',
        'user_id',
        'token',
        'payload',
        'dispatched_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'dispatched_at' => 'datetime',
    ];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(ScheduledPushNotification::class, 'scheduled_push_notification_id');
    }

    public function deviceToken(): BelongsTo
    {
        return $this->belongsTo(DeviceToken::class);
    }
}
