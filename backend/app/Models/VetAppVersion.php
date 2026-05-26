<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VetAppVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_key',
        'platform',
        'min_supported_version',
        'latest_version',
        'force_update',
        'store_url',
        'title',
        'message',
        'is_active',
    ];

    protected $casts = [
        'force_update' => 'boolean',
        'is_active' => 'boolean',
    ];
}
