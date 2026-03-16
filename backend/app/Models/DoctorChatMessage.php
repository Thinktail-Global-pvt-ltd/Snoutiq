<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorChatMessage extends Model
{
    protected $fillable = [
        'doctor_chat_room_id',
        'sender_type',
        'sender_id',
        'message',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(DoctorChatRoom::class, 'doctor_chat_room_id');
    }
}
