<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmergencyRequest extends Model
{
    //
      protected $table = 'emergency_requests';

    protected $fillable = [
        'token',
        'user_id',
        'amount_tobe_paid',
        'is_paid',
        'servicer_id','reason'
    ];

    /**
     * Relationships
     */

    // Emergency request belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // If servicer is also a user in the users table
    public function servicer()
    {
        return $this->belongsTo(User::class, 'servicer_id');
    }
    
}
