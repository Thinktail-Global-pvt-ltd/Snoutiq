<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroomerBooking extends Model
{
    //
    protected $fillable = [
        'serial_number',
        'customer_type',
        'customer_id',
        'customer_pet_id',
        'date',
        'start_time',
        'end_time',
        'services',
        'total',
        'paid',
        'user_id',
        'groomer_employees_id','status','is_inhome',
        'location','prescription','emergency_id'
    ];
     protected $casts = [
       
        'is_inhome' => 'integer',
        'location' => 'array',
    ];
     public function groomerEmployee()
    {
        return $this->belongsTo(GroomerEmployee::class, 'groomer_employees_id');
    }
      public function user()
    {
        return $this->belongsTo(User::class);
    }
}
