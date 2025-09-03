<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroomerProfile extends Model
{
    //
    protected $fillable = [
        'name',
        'bio',
        'address',
        'coordinates',
        'city',
        'pincode',
        'working_hours',
        'user_id','status',
        'profile_picture','inhome_grooming_services',
        'license_no',
'type','chat_price'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
