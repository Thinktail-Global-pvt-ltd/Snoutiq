<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRating extends Model
{
    //
    protected $fillable = [
        'user_id',
        'servicer_id',
        'groomer_booking_id',
        'review',
        'rating',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function servicer()
    {
        return $this->belongsTo(User::class, 'servicer_id');
    }

    public function groomerBooking()
    {
        return $this->belongsTo(GroomerBooking::class, 'groomer_booking_id');
    }
}
