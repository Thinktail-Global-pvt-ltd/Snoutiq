<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroomerCoupon extends Model
{
    //
    protected $table = 'groomer_coupons';
    protected $fillable = ['user_id', 'code', 'discount', 'expiry', 'is_online', 'is_offline'];
    protected $dates = ['expiry'];
    protected $casts = [
        'is_online' => 'boolean',
        'is_offline' => 'boolean',
    ];
}
