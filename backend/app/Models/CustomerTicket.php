<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerTicket extends Model
{
    //
    protected $fillable = [
        'user_id',
        'issue',
        'description',
    ];

    protected $table = 'customer_tickets';
    public function user() 
    {
        return $this->belongsTo(User::class);
    }
}

