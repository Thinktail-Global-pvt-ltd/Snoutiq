<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class GeoStrip extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'min_lat', 'max_lat', 'min_lon', 'max_lon', 'overlap_buffer_km', 'active',
    ];

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('active', true);
    }
}

