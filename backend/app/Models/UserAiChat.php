<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAiChat extends Model
{
    //
    protected $fillable = [
        'title',
        'user_id',
        'token'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
