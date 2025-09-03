<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserChat extends Model
{
    //
    protected $fillable = [
        'user_id',
        'servicer_id',
        'type',
        'message','is_first','paid_amt'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function servicer()
    {
        return $this->belongsTo(User::class, 'servicer_id');
    }
}