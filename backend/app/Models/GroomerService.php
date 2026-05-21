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
       'price_min',
        'price_max',
        'price_after_service',
        'duration',
        'status',
        'service_pic',
        'machinery_image_blob',
        'machinery_image_mime',
        'main_service'
    ];

    protected $hidden = [
        'machinery_image_blob',
    ];

    protected $casts = [
        'price_after_service' => 'boolean',
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
