<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FcmNotification extends Model
{
    use HasFactory;

    protected $table = 'fcm_notifications';

    protected $fillable = [
        'status',
        'target_type',
        'notification_type',
        'delivery_mode',
        'from_source',
        'from_file',
        'from_line',
        'to_target',
        'to_topic',
        'device_token_id',
        'user_id',
        'call_session',
        'owner_model',
        'title',
        'notification_text',
        'provider_message_id',
        'error_code',
        'error_message',
        'data_payload',
        'request_payload',
        'response_payload',
        'sent_at',
    ];

    protected $casts = [
        'data_payload' => 'array',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'sent_at' => 'datetime',
    ];
}
