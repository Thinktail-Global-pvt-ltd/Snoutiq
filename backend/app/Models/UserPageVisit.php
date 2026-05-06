<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPageVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'page_name',
        'session_id',
        'route_path',
        'url',
        'referrer',
        'metadata',
        'entered_at',
        'exited_at',
        'duration_seconds',
    ];

    protected $casts = [
        'metadata' => 'array',
        'entered_at' => 'datetime',
        'exited_at' => 'datetime',
        'duration_seconds' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
