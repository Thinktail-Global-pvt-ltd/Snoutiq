<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroomerService extends Model
{
    //
       protected $fillable = [
        'user_id',
        'groomer_service_category_id',
        'name',
        'description',
        'pet_type',
        'price',
        'duration',
        'status',
        'service_pic','main_service'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(GroomerServiceCategory::class, 'groomer_service_category_id');
    }
}
