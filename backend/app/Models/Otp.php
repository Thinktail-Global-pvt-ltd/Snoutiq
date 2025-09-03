<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    //
        protected $table = 'otps';

    protected $fillable = [
        'token',
        'type',
        'value',
        'otp',
        'expires_at','is_verified'
    ];

    protected $dates = [
        'expires_at',
    ];
}
