<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroomerFeaturedService extends Model
{
    //
    protected $table = 'groomer_featured_services';
    protected $fillable = ['user_id', 'service_id'];

    public function service()
    {
        return $this->belongsTo(GroomerService::class, 'service_id');
    }
}
