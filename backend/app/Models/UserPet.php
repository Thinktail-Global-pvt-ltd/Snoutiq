<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPet extends Model
{
    //

   protected $fillable = [
          'name',
'type',
'breed',
'dob',
'gender',
'pic_link',
'medical_history',
'vaccination_log',
'user_id'    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
