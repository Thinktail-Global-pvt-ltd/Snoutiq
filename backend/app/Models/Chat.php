<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'context_token',
         'chat_room_id',   
           'chat_room_token',  
        'question',
        'answer',
        'pet_name',
        'pet_breed',
        'pet_age',
        'pet_location',
        'emergency_status',
        'diagnosis'

    ];
}
