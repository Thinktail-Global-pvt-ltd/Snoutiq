<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pet extends Model
{
    protected $fillable = [
        'user_id', 'name', 'breed', 'pet_age', 'pet_gender', 'pet_doc1', 'pet_doc2'
    ];
}
