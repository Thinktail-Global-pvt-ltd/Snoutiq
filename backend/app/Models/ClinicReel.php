<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicReel extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'thumb_path',
        'video_path',
        'reel_url',
        'status',
        'order_index',
    ];

    protected $casts = [
        'order_index' => 'integer',
    ];
}
