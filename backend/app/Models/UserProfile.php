<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    //
       protected $fillable = [
        'name',
        'address',
        'city',
        'pincode',
        'profile_pic_link',
        'user_id',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
