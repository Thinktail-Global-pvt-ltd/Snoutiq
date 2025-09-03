<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroomerOffer extends Model
{
    //
    protected $table = 'groomer_offers';
    protected $fillable = ['user_id', 'title', 'discount', 'type', 'service_id', 'category_id', 'expiry'];
    protected $dates = ['expiry'];

    public function service()
    {
        return $this->belongsTo(GroomerService::class, 'service_id');
    }

    public function category()
    {
        return $this->belongsTo(GroomerServiceCategory::class, 'category_id');
    }
}
