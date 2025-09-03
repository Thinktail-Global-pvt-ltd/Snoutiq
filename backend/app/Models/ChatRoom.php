<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatRoom extends Model
{
    protected $fillable = [
        'user_id',
        'chat_room_token',
        'name',
    ];

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }
}
