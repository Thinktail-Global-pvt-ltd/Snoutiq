<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name','email','phone','role','password',
        'pet_name','pet_gender','pet_age','pet_doc1','pet_doc2',
        'api_token_hash','summary'
    ];

    protected $hidden = ['password','remember_token','api_token_hash'];

    protected $casts  = ['pet_age' => 'integer'];

    // // auto-hash password
    // public function setPasswordAttribute($value)
    // {
    //     if ($value) $this->attributes['password'] = Hash::make($value);
    // }
}
