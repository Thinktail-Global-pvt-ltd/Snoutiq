<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAiChatHistory extends Model
{
    //
    protected $table = 'user_ai_chat_histories';

    protected $fillable = [
        'type',
        'message',
        'user_id',
        'user_ai_chat_id','rated'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function userAiChat()
    {
        return $this->belongsTo(UserAiChat::class);
    }
}
