<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;

    public const TYPES = ['critical', 'warning', 'success', 'info'];

    protected $fillable = [
        'type',
        'category',
        'title',
        'message',
        'metadata',
        'is_read',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeType($query, ?string $type)
    {
        if ($type) {
            $query->where('type', $type);
        }

        return $query;
    }
}

