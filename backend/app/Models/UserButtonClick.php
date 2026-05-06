<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserButtonClick extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'page_visit_id',
        'page_name',
        'button_name',
        'button_id',
        'button_text',
        'action_name',
        'session_id',
        'route_path',
        'url',
        'metadata',
        'clicked_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'clicked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pageVisit(): BelongsTo
    {
        return $this->belongsTo(UserPageVisit::class);
    }
}
