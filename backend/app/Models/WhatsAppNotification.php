<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppNotification extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_notifications';

    protected $fillable = [
        'recipient',
        'channel_name',
        'message_type',
        'template_name',
        'language_code',
        'status',
        'http_status',
        'provider_message_id',
        'payload',
        'response_payload',
        'response_body',
        'error_message',
        'error_details',
        'source',
        'source_file',
        'source_line',
        'sent_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'response_payload' => 'array',
        'sent_at' => 'datetime',
    ];
}
