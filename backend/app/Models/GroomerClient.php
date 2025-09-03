<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroomerClient extends Model
{
    //
    protected $fillable = [
        'tag',
        'name',
        'address',
        'city',
        'email',
        'phone',
        'pincode',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
     public function pets()
    {
        return $this->hasMany(GroomerClientPet::class, 'groomer_client_id');
    }
}
