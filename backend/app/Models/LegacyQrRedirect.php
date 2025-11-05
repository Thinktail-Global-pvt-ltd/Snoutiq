<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyQrRedirect extends Model
{
    protected $fillable = [
        'code',
        'legacy_url',
        'clinic_id',
        'public_id',
        'target_url',
        'notes',
        'qr_image_path',
    ];
}
